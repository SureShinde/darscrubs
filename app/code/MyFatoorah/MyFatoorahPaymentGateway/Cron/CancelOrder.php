<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyFatoorah\MyFatoorahPaymentGateway\Cron;

use Psr\Log\LoggerInterface;
//use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\Timezone;

class CancelOrder {

    protected $logger;
    protected $_orderCollectionFactory;
    protected $_stdTimezone;
    protected $log;

    public function __construct(
            LoggerInterface $logger,
            CollectionFactory $orderCollectionFactory,
            Timezone $stdTimezone
    ) {
        $this->logger                  = $logger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_stdTimezone            = $stdTimezone;

        $this->log = new \Zend\Log\Logger();
        $this->log->addWriter(new \Zend\Log\Writer\Stream(BP . '/var/log/myfatoorah.log'));
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    /* public function execute() {

      $duration_in_sec = 60; // 10 MINS
      $currentTime     = $this->_stdTimezone->date((time() - $duration_in_sec))->format('Y-m-d H:i:s');

      $orders = $this->_orderCollectionFactory->create()
      ->addFieldToFilter('created_at', ['lteq' => $currentTime])
      ->addFieldToFilter('status', array('in' => ['pending_payment']));

      $orders->getSelect();
      $order_ids = [];

      foreach ($orders as $order) {
      $order_ids[] = $order->getId();
      $order->addStatusHistoryComment('Order has been canceled automatically', Order::STATE_CANCELED)
      ->setIsVisibleOnFront(true);
      $order->cancel();
      $order->save();
      //->setIsVisibleOnFront(false)
      //->setIsCustomerNotified(false);
      }

      $this->logger->info('cancelled orders ' . implode(' , ', $order_ids));

      return $this;
      } */

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function checkStatusForPendingOrders() {
        $this->log->info('-------------------------------------------------------------------------------------------------------------');
        $this->log->info('In Cron Job: checkStatusForPendingOrders');


        $objectManager = ObjectManager::getInstance();

        //Create Success Object
        $successObj = $objectManager->create('MyFatoorah\MyFatoorahPaymentGateway\Controller\Checkout\Success');

        $ScopeConfigInterface = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $scopeStore           = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        //get db connection
        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');

        //get table name
        $deploymentConfig = $objectManager->get('Magento\Framework\App\DeploymentConfig');
        $prefix           = ($deploymentConfig->get('db/table_prefix'));
        $tableName        = $prefix . 'myfatoorah_invoice';

        //Get Pending orderss
        $orders = $this->_orderCollectionFactory->create()
                ->addFieldToFilter('status', ['in' => ['pending_payment']]);

        //Update Status
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $orderId = $order->getRealOrderId();

            $result1 = $connection->fetchAll("SELECT invoice_id FROM $tableName WHERE order_id=$orderId");

//            $invoiceId = $order->getMyfatoorahInvoiceId();
            if (isset($result1[0]['invoice_id'])) {
                $invoiceId = $result1[0]['invoice_id'];
                $this->log->info("Order #$orderId ----- Cron Job - Check Order Status with Invoice Id #$invoiceId");

                try {

                    $storeId            = $order->getStoreId();
                    $successObj->apiKey = $ScopeConfigInterface->getValue('payment/myfatoorah_gateway/api_key', $scopeStore, $storeId);
                    $isTesting          = $ScopeConfigInterface->getValue('payment/myfatoorah_gateway/is_testing', $scopeStore, $storeId);

                    $successObj->gatewayUrl = 'https://' . ( $isTesting ? 'apitest.myfatoorah.com' : 'api.myfatoorah.com' );

                    $successObj->checkStatus($invoiceId, 'InvoiceId');
               } catch (\Exception $ex) {
                    
                    $err = $ex->getMessage();
                    $order->registerCancellation($err)->save();
//                    $order->cancel();
//                    $order->addStatusHistoryComment($err);
//                    $order->save();
//                    // remove status history set in _setState  ???????????????
//                    $order->getStatusHistoryCollection(true);
                }
            }
        }
        $this->log->info('-------------------------------------------------------------------------------------------------------------');
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
    public function createNewLogFile() {
        $this->log->info('In Cron Job: createNewLogFile');

        $logPath = BP . '/var/log';
        $logFile = "$logPath/myfatoorah.log";

        if (file_exists($logFile)) {

            $mfOldLog = "$logPath/mfOldLog";
            if (!file_exists($mfOldLog)) {
                mkdir($mfOldLog);
}
            rename($logFile, "$mfOldLog/myfatoorah_" . date('Y-m-d') . '.log');
        }
        return true;
    }

//---------------------------------------------------------------------------------------------------------------------------------------------------
}
