<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Emizentech\RevelupWebHook\Observer;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    
    protected $logger;
    protected $_checkoutSession;
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->logger = $logger;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
         try 
			{   
			    $order = $observer->getEvent()->getOrder();
			    $prefix = 'REVEL-';
			    $reveupOrderId = $this->_checkoutSession->getReveupOrderId();
                if($reveupOrderId){
			    $order->setData("increment_id", $prefix.$reveupOrderId)->save();
			    }
			    $reveupOrderId = $this->_checkoutSession->unsReveupOrderId();
			    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				$logger->info('observer event checking');
				$logger->info('observer event checking id '.$reveupOrderId);
			}catch (\Exception $e) 
			{
			
			}
    }
}
