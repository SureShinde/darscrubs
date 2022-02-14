<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool;

use Amasty\Shopby\Model\ConfigProvider;
use Amasty\Shopby\Model\Layer\Filter\OnSale\Helper as OnSaleHelper;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

class OnSale implements OperationInterface
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
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var OnSaleHelper
     */
    private $onSaleHelper;

    public function __construct(
        ConfigProvider $configProvider,
        ResourceConnection $resource,
        ScopeResolverInterface $scopeResolver,
        ProductCollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        OnSaleHelper $onSaleHelper
    ) {
        $this->configProvider = $configProvider;
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->onSaleHelper = $onSaleHelper;
    }

    public function isActive(): bool
    {
        return $this->configProvider->isSaleFilterEnabled();
    }

    public function getAggregation(Table $entityIdsTable, array $dimensions = []): Select
    {
        $currentScope = $dimensions['scope']->getValue();
        $currentScopeId = $this->scopeResolver->getScope($currentScope)->getId();

        /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());

        $collection->addStoreFilter($currentScopeId);
        $this->onSaleHelper->addOnSaleFilter($collection);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('e.entity_id');

        $derivedTable = $this->resource->getConnection()->select();
        $derivedTable->from(
            ['entities' => $entityIdsTable->getName()],
            []
        );

        $derivedTable->joinLeft(
            ['am_on_sale' => $collection->getSelect()],
            'am_on_sale.entity_id  = entities.entity_id',
            [
                'value' => new \Zend_Db_Expr("if(am_on_sale.entity_id is null, 0, 1)")
            ]
        );

        $derivedTable->group('entities.entity_id');

        $select = $this->resource->getConnection()->select();
        $select->from(['main_table' => $derivedTable]);

        return $select;
    }
}
