<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\CatalogSearch\Indexer\Fulltext;

use Amasty\ShopbyBase\Model\Di\Wrapper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class DataProvider
{
    const TYPE_WEBSITE = 'website';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * Eav config
     *
     * @var Config
     */
    private $eavConfig;

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     */
    private $metadata;

    /**
     * @var \Magento\InventorySalesApi\Api\StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var \Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var int
     */
    private $antiGapMultiplier = 5;

    /**
     * @var int[]
     */
    private $productVisibility;

    public function __construct(
        ResourceConnection $resource,
        ScopeConfigInterface $config,
        Config $eavConfig,
        Visibility $productVisibility,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Wrapper $stockResolver,
        Wrapper $defaultStockProvider,
        Wrapper $stockIndexTableNameResolver
    ) {
        $this->resource = $resource;
        $this->config = $config;
        $this->eavConfig = $eavConfig;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->productVisibility = $productVisibility->getVisibleInSiteIds();
        $this->metadata = $metadataPool->getMetadata(ProductInterface::class);
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
    }

    private function getTable(string $table): string
    {
        return $this->resource->getTableName($table);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }

    public function getAttributeByCode(string $attribute)
    {
        return $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
    }

    public function getSearchableProducts(
        int $storeId,
        array $staticFields,
        ?array $productIds = null,
        int $lastProductId = 0,
        int $batch = 100
    ): array {

        $select = $this->getSelectForSearchableProducts($storeId, $staticFields, $productIds, $lastProductId, $batch);
        if ($productIds === null) {
            $select->where(
                'e.entity_id < ?',
                $lastProductId ? $this->antiGapMultiplier * $batch + $lastProductId + 1 : $batch + 1
            );
        }
        $products = $this->getConnection()->fetchAll($select);
        if ($productIds === null && !$products) {
            // try to search without limit entity_id by batch size for cover case with a big gap between entity ids
            $products = $this->getConnection()->fetchAll(
                $this->getSelectForSearchableProducts($storeId, $staticFields, $productIds, $lastProductId, $batch)
            );
        }

        return $products;
    }

    private function getSelectForSearchableProducts(
        int $storeId,
        array $staticFields,
        ?array $productIds,
        int $lastProductId,
        int $batch
    ): Select {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $connection = $this->getConnection();

        $select = $connection->select()
            ->useStraightJoin(true)
            ->from(
                ['e' => $this->getTable('catalog_product_entity')],
                array_merge(['entity_id', 'type_id'], $staticFields)
            )
            ->join(
                ['website' => $this->getTable('catalog_product_website')],
                $connection->quoteInto('website.product_id = e.entity_id AND website.website_id = ?', $websiteId),
                []
            );

        $stockId = $this->getStockId($storeId);
        $displayOutOfStock = (bool) $this->config->getValue('cataloginventory/options/show_out_of_stock');

        if ($stockId === null || $stockId === $this->defaultStockProvider->getId()) {
            $this->addDefaultStockFilter($select, $displayOutOfStock);
        } else {
            $this->addMsiStockFilter($select, $stockId, $displayOutOfStock);
        }

        $this->joinAttribute($select, 'visibility', $storeId, $this->productVisibility);
        $this->joinAttribute($select, 'status', $storeId, [Status::STATUS_ENABLED]);

        if ($productIds !== null) {
            $select->where('e.entity_id IN (?)', $productIds);
        }
        $select->where('e.entity_id > ?', $lastProductId);
        $select->order('e.entity_id');
        $select->limit($batch);

        return $select;
    }

    private function joinAttribute(Select $select, string $attributeCode, int $storeId, array $whereValue): void
    {
        $field = $this->metadata->getLinkField();
        $attribute = $this->getAttributeByCode($attributeCode);
        $attributeTable = $this->getTable('catalog_product_entity_' . $attribute->getBackendType());
        $alias = $attributeCode . '_default';
        $storeAlias = $attributeCode . '_store';

        $whereCondition = $this->getConnection()->getCheckSql(
            $storeAlias . '.value_id > 0',
            $storeAlias . '.value',
            $alias . '.value'
        );

        $select->join(
            [$alias => $attributeTable],
            $this->getConnection()->quoteInto(
                $alias . '.' . $field . '= e.' . $field . ' AND ' . $alias . '.attribute_id = ?',
                $attribute->getAttributeId()
            ) . $this->getConnection()->quoteInto(
                ' AND ' . $alias . '.store_id = ?',
                Store::DEFAULT_STORE_ID
            ),
            []
        )->joinLeft(
            [$storeAlias => $attributeTable],
            $this->getConnection()->quoteInto(
                $storeAlias . '.' . $field . '= e.' . $field . ' AND ' . $storeAlias . '.attribute_id = ?',
                $attribute->getAttributeId()
            ) . $this->getConnection()->quoteInto(
                ' AND ' . $storeAlias . '.store_id = ?',
                $storeId
            ),
            []
        )->where(
            $whereCondition . ' IN (?)',
            $whereValue
        );
    }

    public function getStockId(int $storeId): ?int
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        $stock = $this->stockResolver->execute(self::TYPE_WEBSITE, $websiteCode);
        return $stock ? $stock->getStockId() : null;
    }

    public function addDefaultStockFilter(Select $select, bool $displayOutOfStock = false): void
    {
        $stockStatusTable = $this->resource->getTableName('cataloginventory_stock_status');
        $select->joinInner(
            ['stock_index' => $stockStatusTable],
            'stock_index.product_id = e.entity_id',
            []
        );

        if (!$displayOutOfStock) {
            $select->where('stock_index.stock_status = 1');
        }
    }

    public function addMsiStockFilter(Select $select, int $stockId, bool $displayOutOfStock = false): void
    {
        $stockIndexTableName = $this->stockIndexTableNameResolver->execute((int)$stockId);
        if (!$stockIndexTableName) {
            $this->addDefaultStockFilter($select, $displayOutOfStock);
        } else {
            $select->joinInner(['stock_index' => $stockIndexTableName], 'e.sku = stock_index.sku', []);

            if (!$displayOutOfStock) {
                $select->where('stock_index.is_salable = 1');
            }
        }
    }
}
