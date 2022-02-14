<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel;

use Magento\CatalogInventory\Model\Stock;

class GetMsiInStockProductIds extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var array
     */
    private $stockIds;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->stockIds = [];
    }

    public function execute(array $productIds, int $storeId): array
    {
        $select = $this->getConnection()->select()
            ->from(
                ['stock' => $this->getTable('inventory_stock_' . $this->getStockId($storeId))],
                ['catalog_product_entity.entity_id']
            )
            ->join(
                ['catalog_product_entity' => $this->getTable('catalog_product_entity')],
                'catalog_product_entity.sku = stock.sku',
                []
            )
            ->where('catalog_product_entity.entity_id IN (?)', $productIds)
            ->where('is_salable = ?', Stock::STOCK_IN_STOCK);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getStockId(int $storeId): int
    {
        if (!isset($this->stockIds[$storeId])) {
            $select = $this->getConnection()->select()
                ->from(['stock' => $this->getTable('inventory_stock_sales_channel')], ['stock_id'])
                ->join(
                    ['store_website' => $this->getTable('store_website')],
                    'store_website.code = stock.code',
                    []
                )
                ->join(
                    ['store' => $this->getTable('store')],
                    'store.website_id = store_website.website_id',
                    []
                )
                ->where('store_id = ?', $storeId)
                ->where('stock.type = \'website\'');

            $this->stockIds[$storeId] = (int)$this->getConnection()->fetchOne($select);
        }

        return $this->stockIds[$storeId];
    }
}
