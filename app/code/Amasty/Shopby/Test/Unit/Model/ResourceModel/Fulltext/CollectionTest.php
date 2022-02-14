<?php

namespace Amasty\Shopby\Test\Unit\Model\ResourceModel\Fulltext;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver;
use Amasty\Shopby\Model\Search\SearchCriteriaBuilderProvider;
use Amasty\Shopby\Test\Unit\Traits;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver as MysqlSearchCriteriaResolver;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\SearchCriteria;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Collection
 *
 * @see Collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    const STORE_ID = '0';

    const TEST_FIELD = 'test';

    const TEST_FAIL_FIELD = 'test_fail';

    /**
     * @var Collection|MockObject
     */
    private $collection;

    public function setup(): void
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $scopeConfig = $this->createMock(\Magento\Framework\App\Config::class);
        $searchCriteriaBuilderProvider = $this->createMock(SearchCriteriaBuilderProvider::class);
        $filterBuilder = $this->createMock(\Magento\Framework\Api\FilterBuilder::class);
        $filter = $this->createMock(Filter::class);
        $searchCriteriaResolver = $this->createMock(SearchCriteriaResolver::class);
        $resolver = $this->createMock(MysqlSearchCriteriaResolver::class);

        $scopeConfig->expects($this->any())->method('getValue')
            ->with(\Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory::XML_PATH_RANGE_CALCULATION)
            ->willReturn('test');
        $searchCriteriaResolver->expects($this->any())->method('getResolver')->willReturn($resolver);
        $filterBuilder->expects($this->any())->method('create')->willReturn($filter);

        $this->setProperty($this->collection, '_scopeConfig', $scopeConfig, Collection::class);
        $this->setProperty($this->collection, 'filterBuilder', $filterBuilder, Collection::class);
        $this->setProperty($this->collection, 'searchCriteriaResolver', $searchCriteriaResolver, Collection::class);
        $this->setProperty(
            $this->collection,
            'memCriteriaBuilderProvider',
            $searchCriteriaBuilderProvider,
            Collection::class
        );
    }

    /**
     * @covers Collection::getMemSearchCriteria
     */
    public function testGetMemSearchCriteria()
    {
        $this->collection->expects($this->any())->method('getStoreId')->willReturn(self::STORE_ID);

        $result = $this->collection->getMemSearchCriteria();
        $this->assertInstanceOf(SearchCriteria::class, $result);

        $result = $this->collection->getMemSearchCriteria();
        $this->assertInstanceOf(SearchCriteria::class, $result);
    }

    /**
     * @covers Collection::getFacetedData
     *
     * @expectedException \Magento\Framework\Exception\StateException
     */
    public function testGetFacetedData()
    {
        $agregationValue = $this->createMock(\Magento\Framework\Search\Response\Aggregation\Value::class);
        $agregationValue->expects($this->any())->method('getMetrics')
            ->willReturn(
                ['value' => 'test_val']
            );

        $bucket = $this->getObjectManager()->getObject(
            \Magento\Framework\Search\Response\Bucket::class,
            ['values' => [$agregationValue]]
        );

        $aggregation = $this->getObjectManager()
            ->getObject(
                \Magento\Framework\Search\Response\Aggregation::class,
                ['buckets' => ['test_bucket' => $bucket]]
            );

        $queryResponse = $this->createMock(\Magento\Framework\Search\Response\QueryResponse::class);
        $queryResponse->expects($this->any())->method('getAggregations')->willReturn($aggregation);

        $result = $this->collection->getFacetedData(self::TEST_FIELD, $queryResponse);

        $this->assertEquals(['test_val' => ['value' => 'test_val']], $result);

        $this->expectException(\Magento\Framework\Exception\StateException::class);
        $this->collection->getFacetedData(self::TEST_FAIL_FIELD, $queryResponse);
    }
}
