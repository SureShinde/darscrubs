<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool;

use Amasty\Shopby\Model\ConfigProvider;
use Amasty\Shopby\Model\Inventory\Resolver as InventoryResolver;
use Magento\CatalogInventory\Api\StockConfigurationInterface as StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockResource;

class StockStatus implements OperationInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StockResource
     */
    private $stockResource;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var InventoryResolver
     */
    private $inventoryResolver;

    public function __construct(
        ConfigProvider $configProvider,
        ResourceConnection $resource,
        StockResource $stockResource,
        ScopeResolverInterface $scopeResolver,
        StockConfigurationInterface $stockConfiguration,
        InventoryResolver $inventoryResolver
    ) {
        $this->configProvider = $configProvider;
        $this->resource = $resource;
        $this->stockResource = $stockResource;
        $this->scopeResolver = $scopeResolver;
        $this->stockConfiguration = $stockConfiguration;
        $this->inventoryResolver = $inventoryResolver;
    }

    public function isActive(): bool
    {
        return $this->configProvider->isStockFilterEnabled();
    }

    public function getAggregation(Table $entityIdsTable, array $dimensions = []): Select
    {
        $aggregationSelect = $this->resource->getConnection()->select();
        if ($this->isStockSourceQty()) {
            $this->addStockSourceAggregation($aggregationSelect, $entityIdsTable);
        } else {
            $this->addStatusSourceAggregation($aggregationSelect, $entityIdsTable);
        }

        $select = $this->resource->getConnection()->select();
        $select->from(['main_table' => $aggregationSelect]);

        return $select;
    }

    /**
     * @return bool
     */
    private function isStockSourceQty()
    {
        $stockSource = $this->configProvider->getStockSource();

        return $stockSource === \Amasty\Shopby\Model\Source\StockFilterSource::QTY;
    }

    private function addStockSourceAggregation(Select $select, Table $table): void
    {
        $storeId = $this->scopeResolver->getScope()->getId();
        $qty = (float)$this->stockConfiguration->getMinQty($storeId);
        $cond = "type_id != 'simple' OR qty > IF(use_config_min_qty, $qty, min_qty)";
        $select->from(
            ['e' => $this->resource->getTableName('catalog_product_entity')]
        )->joinInner(
            ['entities' => $table->getName()],
            'e.entity_id  = entities.entity_id',
            []
        )->joinLeft(
            ['at_qty' => $this->resource->getTableName('pref_cataloginventory_stock_item')],
            'at_qty.product_id = e.entity_id AND at_qty.stock_id = 1',
            ['value' => new \Zend_Db_Expr("IF($cond, 1, 0)")]
        );
    }

    private function addStatusSourceAggregation(Select $select, Table $table): void
    {

        $select->from(
            ['e' => $this->resource->getTableName('catalog_product_entity')]
        )->joinInner(
            ['entities' => $table->getName()],
            'e.entity_id  = entities.entity_id',
            []
        );

        $this->stockResource->addStockStatusToSelect($select, $this->scopeResolver->getScope()->getWebsite());

        $catalogInventoryTable = $this->stockResource->getMainTable();
        $fromTables = $select->getPart(Select::FROM);

        if ($this->inventoryResolver->isMsiEnabled()
            && $fromTables['stock_status']['tableName'] != $catalogInventoryTable
        ) {
            $stockStatusColumn = 'is_salable';
        } else {
            $stockStatusColumn = 'stock_status';
            $fromTables['stock_status']['joinCondition'] = $this->inventoryResolver->replaceWebsiteWithDefault(
                $fromTables['stock_status']['joinCondition']
            );
            $select->setPart(Select::FROM, $fromTables);
        }

        $select->columns(['value' => 'stock_status.' . $stockStatusColumn]);
    }
}
