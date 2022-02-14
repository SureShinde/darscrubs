<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout
 */
class Success extends AbstractAction {

    private $cards = [
        'KNET'              => 'kn',
        'VISA/MASTER'       => 'vm',
        'MADA'              => 'md',
        'Benefit'           => 'b',
        'Qatar Debit Cards' => 'np',
        'UAE Debit Cards'   => 'uaecc',
        'Sadad'             => 's',
        'AMEX'              => 'ae',
        'Apple Pay'         => 'ap',
        'KFast'             => 'kf',
        'AFS'               => 'af',
        'STC Pay'           => 'stc'
    ];

    public function execute() {

        try {
            $paymentId = $this->getRequest()->get('paymentId');
            if (!$paymentId) {

                $err = 'MyFatoorah returned a null payment id. This may indicate an issue with the myfatoorah payment gateway.';

                $this->log->info("Get Payment Status - Error: $err");
                throw new \Exception($err);
            }


            if ($this->checkStatus($paymentId, 'paymentId')) {
                $this->getMessageManager()->addSuccessMessage(__('Your payment is complete'));
                //???? search foce empty cart if 1st time is faild senairo 
            }

            $this->_redirect('checkout/onepage/success', array('_secure' => false));
        } catch (\Exception $ex) {
            $err = $ex->getMessage();
            $this->getCheckoutHelper()->cancelCurrentOrder($err);

            $this->getMessageManager()->addErrorMessage($err);

            //restore cart
            $this->getCheckoutHelper()->restoreQuote();

            $this->_redirect('checkout/cart', array('_secure' => false));
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function checkStatus($keyId, $KeyType) {

        $curlData = ['Key' => $keyId, 'KeyType' => $KeyType];

        $json = $this->callAPI("$this->gatewayUrl/v2/GetPaymentStatus", $curlData, $keyId, 'Get Payment Status');


        $orderId = $json->Data->CustomerReference;
        $order   = $this->getOrderById($orderId);

        $msgLog = "Order #$orderId ----- Get Payment Status";

        if (!$order) {
            $err = "MyFatoorah returned an id:$orderId for an order that could not be retrieved";

            $this->log->info("$msgLog - Error: $err");
            throw new \Exception($err);
        }


        $lastInvoiceTransactions = end($json->Data->InvoiceTransactions);

        //save the invoice id in myfatoorah_invoice table 
        $collection = $this->mfInvoiceFactory->create()->addFieldToFilter('order_id', $orderId);
        $item       = $collection->getFirstItem();
        if ($item && $lastInvoiceTransactions) {
            $itemData = $item->getData();

            if ($itemData['gateway_id'] == 'myfatoorah') {
                $item->setData('gateway_id', $this->cards[$lastInvoiceTransactions->PaymentGateway]);
            }
            $item->setData('gateway_transaction_id', $lastInvoiceTransactions->TransactionId);
            $item->save();
        }


        if ($json->Data->InvoiceStatus != 'Paid') {

            $err = '';


            if ($lastInvoiceTransactions) {
                $err = $lastInvoiceTransactions->Error;
            } else {

                //all myfatoorah gateway is set to Asia/Kuwait
                $ExpiryDate  = new \DateTime($json->Data->ExpiryDate, new \DateTimeZone('Asia/Kuwait'));
                $ExpiryDate->modify('+1 day'); ///????????????$ExpiryDate without any hour so for i added the 1 day just in case. this should be changed after adding the tome to the expire date
                $currentDate = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));

                if ($ExpiryDate < $currentDate) {
                    $err = 'Invoice is expired since: ' . $ExpiryDate->format('Y-m-d');
                }
            }

            if ($err) {
                $this->log->info("$msgLog - Result: Failed with Error: $err");
                throw new \Exception($err);
            }
            //payment is pending .. user has not paid yet and the invoice is not expired

            $this->log->info("$msgLog - Result: Pending Payment");
            return false;
        }


        $this->log->info("$msgLog - Result: Paid");

        $processing = Order::STATE_PROCESSING;
        if ($order->getState() !== Order::STATE_PENDING_PAYMENT && $order->getState() !== Order::STATE_CANCELED) {
            return true;
        }

        $invoiceId = $json->Data->InvoiceId;


        $orderStatus = $this->getGatewayConfig()->getMyFatoorahApprovedOrderStatus();
        if (!$this->statusExists($orderStatus)) {
            $orderStatus = $order->getConfig()->getStateDefaultStatus($processing);
        }

        $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

        //set order status
        $order->setState($processing)
                ->setStatus($orderStatus)
                ->addStatusHistoryComment("MyFatoorah authorisation success. Invoice #$invoiceId. Gateway used is $lastInvoiceTransactions->PaymentGateway.")
                ->setIsCustomerNotified($emailCustomer);

        //set payment
        $payment = $order->getPayment();
        $payment->setTransactionId($invoiceId);
        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
        $order->save();

        //send email
        $emailSender = $this->getObjectManager()->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
        $emailSender->send($order);

        $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
        if ($invoiceAutomatically) {
            $this->invoiceOrder($order, $invoiceId, $orderId);
        }


        return true;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function statusExists($orderStatus) {
        $statuses = $this->getObjectManager()
                ->get('Magento\Sales\Model\Order\Status')
                ->getResourceCollection()
                ->getData();
        foreach ($statuses as $status) {
            if ($orderStatus === $status['status']) {
                return true;
            }
        }

        return false;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    private function invoiceOrder($order, $invoiceId, $orderId) {
        $this->log->info('In Create Invoice ----- Order# ' . $orderId);
        if ($order->canInvoice()) {
            $this->log->info('Can Create Invoice ----- Order# ' . $orderId);


            $invoice = $this->getObjectManager()
                    ->create('Magento\Sales\Model\Service\InvoiceService')
                    ->prepareInvoice($order);

            if (!$invoice->getTotalQty()) {
                $this->log->info('Can\'t create an invoice without products. ----- Order# ' . $orderId);
            }

            /*
             * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
             * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and 
             * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
             */
            $invoice->setTransactionId($invoiceId);
            $invoice->setRequestedCaptureCase(Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $transaction = $this->getObjectManager()->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transaction->save();
        } else {
            $this->log->info('Can\'t create an invoice. ----- Order# ' . $orderId);
        }
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
