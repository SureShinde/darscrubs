<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search\FilterMapper\CustomExclusionStrategy;

use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Review\Model\ResourceModel\Review as ReviewResource;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

class Rating implements OperationInterface
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
     * @var ReviewResource
     */
    private $reviewResource;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        ReviewResource $reviewResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->reviewResource = $reviewResource;
    }

    public function applyFilter(FilterInterface $filter, Select $select): void
    {
        $alias = $filter->getField() . RequestGenerator::FILTER_SUFFIX;
        $select->joinLeft(
            [$alias => $this->resourceConnection->getTableName('review_entity_summary')],
            sprintf(
                '`rating_summary_filter`.`entity_pk_value`=`search_index`.entity_id
                AND `rating_summary_filter`.entity_type = %d
                AND `rating_summary_filter`.store_id  =  %d',
                $this->reviewResource->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE),
                $this->storeManager->getStore()->getId()
            ),
            []
        );
    }
}
