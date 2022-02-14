<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;

/**
 * @package MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout
 */
class APIResponse extends AbstractAction {

    public function execute() {
        $paymentId = $this->getRequest()->get("paymentId");
        if (!$paymentId) {
            $this->getLogger()->debug("MyFatoorah returned a null order id. This may indicate an issue with the myfatoorah payment gateway.");
            $this->_redirect('checkout/onepage/error', array('_secure' => false));

            return;
        }


        $token = $this->getGatewayConfig()->getApiKey();
        $gatewayURL = $this->getGatewayConfig()->getGatewayUrl();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$gatewayURL/v2/GetPaymentStatus",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{
                            "Key": "' . $paymentId . '",
                            "KeyType": "paymentId"
                          }',
            CURLOPT_HTTPHEADER => array("Authorization: Bearer $token", "Content-Type: application/json"),
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $errMsg = '';

        if ($err) {
            $errMsg = "Direct payment - cURL Error #:" . $err;
            $this->getLogger()->debug($errMsg);
            $this->_redirect('checkout/onepage/error', array('_secure' => false));

            return;
        } else {

            $json = json_decode((string) $response, true);
            $transactionId = '';
            if ($json['Data']['InvoiceStatus'] == 'Paid') {
                $transactionId = $json['Data']['InvoiceId'];
                $result = "completed";
            } else {
                $result = "failed";
                $errMsg = $json['Data']['InvoiceTransactions'][0]['Error'];
            }
            $orderId = $json['Data']['CustomerReference'];
            if (!empty($this->getRequest()->get("apicid"))) {

                $this->writer = new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorahAPI.log');
                $log = new \Zend\Log\Logger();
                $log->addWriter($this->writer);
                $cartId = base64_decode($this->getRequest()->get("apicid"));

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $quote = $objectManager->create('Magento\Quote\Model\Quote')->loadByIdWithoutStore($cartId);
                $quote->getPayment()->setMethod('myfatoorah_gateway');

                $quote->setIsActive(true)->save();
                $order = $this->getOrderById($orderId);

                if ($result == 'completed') {

                    // place order
                    $quote->setCustomerEmail($quote->getShippingAddress()->getEmail());
                    if ($quote->getCustomerId()) {
                        $log->info("======================= Logged in User " . $quote->getCustomerId() . " ========================");
                    } else {
                        $log->info("======================= Guest User ========================");
                        $quote->setCheckoutMethod('guest')
                                ->setCustomerId(null);
                    }
                    try {
                        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                        $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                        $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

                        $order->setState($orderState)
                                ->setStatus($orderStatus)
                                ->addStatusHistoryComment("MyFatoorah authorisation success. Transaction #$transactionId")
                                ->setIsCustomerNotified($emailCustomer);

                        $payment = $order->getPayment();
                        $payment->setTransactionId($transactionId);
                        $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
                        $order->save();
                        $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                        $emailSender->send($order);

                        $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
                        if ($invoiceAutomatically) {
                            $this->invoiceOrder($order, $transactionId);
                        }
                        $log->info("======================= Order Placed ========================");
                        // redirect to success page
                        $quote->setIsActive(false)->save();

                        $this->_redirect('checkout/onepage/success', array('_secure' => false));

                        return;
                    } catch (\Exception $e) {
                        $log->info($e);
                        $log->info("======================= Order not Placed ========================");
                        $this->getMessageManager()->addWarningMessage(__("There's a problem occured. Please contact us to check this."));

                        $this->getCheckoutHelper()->cancelCurrentOrder("Order #" . ( $order->getId() ) . "There's a problem occured. PaymentId #$paymentId.");
                        $this->getCheckoutHelper()->restoreQuote(); //restore cart
                        $this->_redirect('checkout/cart', array('_secure' => false));
                    }
                } else {
                    $this->getMessageManager()->addWarningMessage(__("Your order has been canceled or declined by Payment Gateway. Please click on 'Update Shopping Cart'."));

                    $this->getCheckoutHelper()->cancelCurrentOrder("Order #" . ( $order->getId() ) . " was rejected by myfatoorah. PaymentId #$paymentId.");
                    $this->getCheckoutHelper()->restoreQuote(); //restore cart
                    $this->_redirect('checkout/cart', array('_secure' => false));
                }
            }
        }
    }

    private function invoiceOrder($order, $transactionId) {
        if (!$order->canInvoice()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                    __('Cannot create an invoice.')
            );
        }

        $invoice = $this->getObjectManager()
                ->create('Magento\Sales\Model\Service\InvoiceService')
                ->prepareInvoice($order);

        if (!$invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                    __('You can\'t create an invoice without products.')
            );
        }

        /*
         * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
         * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and 
         * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
         */
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = $this->getObjectManager()->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
        $transaction->save();
    }

}
