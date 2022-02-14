<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Catalog\Product\Indexer\Eav;

use Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\AbstractEav;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Module\Manager;
use Magento\Store\Api\StoreRepositoryInterface;

class DeleteOutOfStockChild extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const ADMIN_WEBSITE = 0;

    /**
     * @var array
     */
    private $stockIds;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        StoreRepositoryInterface $storeRepository,
        Manager $moduleManager,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->storeRepository = $storeRepository;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->stockIds = [];
    }

    public function execute(AbstractEav $subject): void
    {
        $connection = $this->getConnection();
        $idxTable = $subject->getIdxTable();

        $select = $connection->select()->from($idxTable, null);
        $select->joinInner(
            ['cpe' => $this->getTable('catalog_product_entity')],
            sprintf(
                'cpe.entity_id = %s.entity_id AND cpe.type_id = "%s"',
                $idxTable,
                Configurable::TYPE_CODE
            ),
            []
        );
        if ($this->isMsiEnabled()) {
            $this->joinMsiStock($select, $idxTable);
        } else {
            $this->joinDefaultStock($select, $idxTable);
        }

        $query = $select->deleteFromSelect($idxTable);
        $connection->query($query);
    }

    private function joinDefaultStock(Select $select, string $idxTable): void
    {
        $select->joinInner(
            ['stock_status' => $this->getTable('cataloginventory_stock_status')],
            sprintf(
                'stock_status.product_id = %s.source_id AND stock_status.stock_status = %s',
                $idxTable,
                Stock::STOCK_OUT_OF_STOCK
            ),
            []
        );
        $select->join(
            ['store' => $this->getTable('store')],
            sprintf(
                'stock_status.website_id IN (%s, store.website_id) AND store.store_id = %s.store_id',
                self::ADMIN_WEBSITE,
                $idxTable
            ),
            []
        );
    }

    private function joinMsiStock(Select $select, string $idxTable): void
    {
        $select->joinInner(
            ['cpe_simple' => $this->getTable('catalog_product_entity')],
            sprintf(
                'cpe_simple.entity_id = %s.source_id ',
                $idxTable
            ),
            []
        );
        foreach ($this->storeRepository->getList() as $store) {
            $storeId = (int)$store->getId();
            if ($storeId) {
                $stockId = $this->getStockId($storeId);
                $alias = 'stock_' . $stockId;
                if (!isset($select->getPart('from')[$alias])) {
                    $select->joinInner(
                        [$alias => $this->getTable('inventory_stock_' . $stockId)],
                        sprintf(
                            'cpe_simple.sku = %s.sku AND %s.is_salable = %s',
                            $alias,
                            $alias,
                            Stock::STOCK_OUT_OF_STOCK
                        ),
                        []
                    );
                }
            }
        }
    }

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

    public function isMsiEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
