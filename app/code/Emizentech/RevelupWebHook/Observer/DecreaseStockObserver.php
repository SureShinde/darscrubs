<?php

namespace Emizentech\RevelupWebHook\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\Event\Observer;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\CatalogInventory\Observer\ProductQty;

class DecreaseStockObserver implements ObserverInterface
{

    /**
     * @var StockManagementInterface
     */
    private $stockManagement;

    /**
     * @var ItemsForReindex
     */
    private $itemsForReindex;
    /**
     * @var ProductQty
     */
    private $productQty;

    /**
     * SubtractQuoteInventoryObserver constructor.
     * @param StockManagementInterface $stockManagement
     * @param ProductQty $productQty
     * @param ItemsForReindex $itemsForReindex
     */
    public function __construct(
        StockManagementInterface $stockManagement,
        ProductQty $productQty,
        ItemsForReindex $itemsForReindex
    ) {
        $this->stockManagement = $stockManagement;
        $this->productQty = $productQty;
        $this->itemsForReindex = $itemsForReindex;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $items = $this->productQty->getProductQty($event->getItems());
        $itemsForReindex = $this->stockManagement->registerProductsSale(
            $items
        );
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info('observer event checking ddddddddddd');
        $this->itemsForReindex->setItems($itemsForReindex);
    }
}