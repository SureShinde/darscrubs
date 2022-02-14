<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel;

use Magento\CatalogInventory\Model\Stock;
use Magento\Store\Model\Store;

class GetInStockProductIds extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init($this->getTable('cataloginventory_stock_status'), 'product_id');
    }

    public function execute(array $productIds, int $storeId): array
    {
        $select = $this->getConnection()->select()
            ->from(['stock_status' => $this->getTable('cataloginventory_stock_status')], ['product_id'])
            ->join(
                ['store' => $this->getTable('store')],
                'stock_status.website_id = store.website_id',
                []
            )
            ->where('product_id IN (?)', $productIds)
            ->where('stock_status = ?', Stock::STOCK_IN_STOCK)
            ->where('store_id IN (?)', [$storeId, Store::DEFAULT_STORE_ID]);

        return $this->getConnection()->fetchCol($select);
    }
}
