<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool;

use Amasty\Shopby\Model\ConfigProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;
use Magento\Review\Model\Review;

class Rating implements OperationInterface
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
     * @var ReviewResource
     */
    private $reviewResource;

    public function __construct(
        ConfigProvider $configProvider,
        ResourceConnection $resource,
        ScopeResolverInterface $scopeResolver,
        ReviewResource $reviewResource
    ) {
        $this->configProvider = $configProvider;
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->reviewResource = $reviewResource;
    }

    public function isActive(): bool
    {
        return $this->configProvider->isRatingFilterEnabled();
    }

    public function getAggregation(Table $entityIdsTable, array $dimensions = []): Select
    {
        $scopeId = isset($dimensions['scope']) ? $dimensions['scope']->getValue() : null;

        $currentScopeId = $this->scopeResolver->getScope($scopeId)->getId();
        $derivedTable = $this->resource->getConnection()->select();
        $derivedTable->from(
            ['entities' => $entityIdsTable->getName()],
            []
        );

        $columnRating = new \Zend_Db_Expr("TRUNCATE(main_table.rating_summary / 20, 0)");

        $derivedTable->joinLeft(
            ['main_table' => $this->resource->getTableName('review_entity_summary')],
            sprintf(
                '`main_table`.`entity_pk_value`=`entities`.entity_id
                AND `main_table`.entity_type = %d
                AND `main_table`.store_id  =  %d',
                $this->reviewResource->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE),
                $currentScopeId
            ),
            [
                'value' => $columnRating,
            ]
        );

        $select = $this->resource->getConnection()->select();
        $select->from(['main_table' => $derivedTable]);

        return $select;
    }
}
