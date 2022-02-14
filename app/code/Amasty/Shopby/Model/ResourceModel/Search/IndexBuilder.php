<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search;

use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

class IndexBuilder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockStatusResource
     */
    private $stockResource;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        StockStatusResource $stockResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->stockResource = $stockResource;
    }

    public function addStockDataToSelect(Select $select): void
    {
        $select->joinInner(
            ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'search_index.entity_id = e.entity_id',
            []
        );

        $this->stockResource->addStockStatusToSelect($select, $this->storeManager->getStore()->getWebsite());
        $this->renameStockTable($select);
    }

    /**
     * @param Select $select
     */
    private function renameStockTable(Select $select): void
    {
        // remove unused alias
        $columns = $select->getPart(Select::COLUMNS);
        array_pop($columns);
        $select->setPart(Select::COLUMNS, $columns);

        // rename stock table in stock_status_filter
        $from = $select->getPart(Select::FROM);
        $stockStatus = $from['stock_status'];
        unset($from['stock_status']);
        $stockStatus['joinCondition'] = str_replace(
            'stock_status',
            'stock_status_filter',
            $stockStatus['joinCondition']
        );

        $from['stock_status_filter'] = $stockStatus;
        $select->setPart(Select::FROM, $from);
    }
}
