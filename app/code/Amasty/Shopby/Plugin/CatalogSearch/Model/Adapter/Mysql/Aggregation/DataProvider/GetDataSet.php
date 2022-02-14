<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider;

use Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Request\BucketInterface;

class GetDataSet
{
    /**
     * @var CustomFilterPool
     */
    private $filterPool;

    public function __construct(CustomFilterPool $filterPool)
    {
        $this->filterPool = $filterPool;
    }

    /**
     * @param \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject
     * @param \Closure $proceed
     * @param BucketInterface $bucket
     * @param array $dimensions
     * @param Table $entityIdsTable
     * @return \Magento\Framework\DB\Select|mixed
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function aroundGetDataSet(
        \Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider $subject,
        \Closure $proceed,
        BucketInterface $bucket,
        array $dimensions,
        Table $entityIdsTable
    ) {
        $aggregation = $this->filterPool->getAggregation($bucket->getField(), $entityIdsTable, $dimensions);

        return $aggregation ?: $proceed($bucket, $dimensions, $entityIdsTable);
    }
}
