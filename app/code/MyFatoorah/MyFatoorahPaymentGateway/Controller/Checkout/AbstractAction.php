<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout;

use MyFatoorah\MyFatoorahPaymentGateway\Gateway\Config\Config;
use MyFatoorah\MyFatoorahPaymentGateway\Helper\Checkout;
use MyFatoorah\MyFatoorahPaymentGateway\Helper\Crypto;
use MyFatoorah\MyFatoorahPaymentGateway\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use MyFatoorah\MyFatoorahPaymentGateway\Model\ResourceModel\MyfatoorahInvoice\CollectionFactory;

/**
 * @package MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout
 */
abstract class AbstractAction extends Action {

//    const LOG_FILE = 'myfatoorah.log';

    private $_context;
    private $_checkoutSession;
    private $_orderFactory;
    private $_cryptoHelper;
    private $_dataHelper;
    private $_checkoutHelper;
    protected $_gatewayConfig;
    private $_messageManager;
    protected $mfInvoiceFactory;
    private $_logger;

    public function __construct(
            Config $gatewayConfig,
            Session $checkoutSession,
            Context $context,
            OrderFactory $orderFactory,
            Crypto $cryptoHelper,
            Data $dataHelper,
            Checkout $checkoutHelper,
            CollectionFactory $mfInvoiceFactory,
            LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory    = $orderFactory;
        $this->_cryptoHelper    = $cryptoHelper;
        $this->_dataHelper      = $dataHelper;
        $this->_checkoutHelper  = $checkoutHelper;
        $this->_gatewayConfig   = $gatewayConfig;
        $this->_messageManager  = $context->getMessageManager();
        $this->mfInvoiceFactory = $mfInvoiceFactory;
        $this->_logger          = $logger;

        $this->apiKey     = $gatewayConfig->getApiKey();
        $this->gatewayUrl = $gatewayConfig->getGatewayUrl();

        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
        
    }

    protected function getContext() {
        return $this->_context;
    }

    protected function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    protected function getOrderFactory() {
        return $this->_orderFactory;
    }

    protected function getCryptoHelper() {
        return $this->_cryptoHelper;
    }

    protected function getDataHelper() {
        return $this->_dataHelper;
    }

    protected function getCheckoutHelper() {
        return $this->_checkoutHelper;
    }

    protected function getGatewayConfig() {
        return $this->_gatewayConfig;
    }

    protected function getMessageManager() {
        return $this->_messageManager;
    }

    protected function getLogger() {
        return $this->_logger;
    }

    protected function getOrder() {
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order) {
            throw new \Exception('Unable to get order from last loaded order id. Possibly related to a failed database call');
        }
        return $order;
    }

    protected function getOrderById($orderId) {
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function getObjectManager() {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    protected function callAPI($url, $postFields, $orderId, $function) {

        $fields = json_encode($postFields);

        $msgLog = "Order #$orderId ----- $function";
        $this->log->info("$msgLog - Request: $fields");


        //***************************************
        //call url
        //***************************************
        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => array("Authorization: Bearer $this->apiKey", 'Content-Type: application/json'),
            CURLOPT_RETURNTRANSFER => true,
        ));

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        //***************************************
        //check for errors
        //***************************************
        //example set a local ip to host apitest.myfatoorah.com
        if ($err) {
            $this->log->info("$msgLog - cURL Error: $err");
            throw new \Exception($err);
        }

        $this->log->info("$msgLog - Response: $res");

        $json = json_decode($res);
        if (!isset($json->IsSuccess) || $json->IsSuccess == null || $json->IsSuccess == false) {

            //check for the error insde the object Please tell the exact postion and dont use else
            if (isset($json->ValidationErrors)) {
                $err = implode(', ', array_column($json->ValidationErrors, 'Error'));
            } else if (isset($json->Data->ErrorMessage)) {
                $err = $json->Data->ErrorMessage;
            }

            //if not get the message. this is due that sometimes errors with ValidationErrors has Error value null so either get the "Name" key or get the "Message"
            //example {"IsSuccess":false,"Message":"Invalid data","ValidationErrors":[{"Name":"invoiceCreate.InvoiceItems","Error":""}],"Data":null}
            //example {"Message":"No HTTP resource was found that matches the request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'.","MessageDetail":"No route providing a controller name was found to match request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'"}
            if (empty($err)) {
                $err = (isset($json->Message)) ? $json->Message : __('Transaction failed with unknown error.');
            }

            $this->log->info("$msgLog - Error: $err");
            throw new \Exception($err);
        }


        //***************************************
        //Success 
        //***************************************
        return $json;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    protected function getPendingOrderLifetime() {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $ScopeConfigInterface */
        $ScopeConfigInterface = $this->getObjectManager()->create('\Magento\Framework\App\Config\ScopeConfigInterface');

        return $ScopeConfigInterface->getValue('sales/orders/delete_pending_after', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
}

//---------------------------------------------------------------------------------------------------------------------------------------------------   
}
