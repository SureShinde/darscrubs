<?php

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Test\Unit\Traits;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Search\Api\SearchInterface;

/**
 * Class CategoryTest
 *
 * @see Category
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class CategoryTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var \Amasty\Shopby\Model\Layer\Filter\Category
     */
    private $model;

    /**
     * @var SearchCriteria
     */
    private $searchCriteria;

    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    public function setup(): void
    {
        $this->model = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRenderCategoriesLevel',
                'isRenderAllTree',
                'isMultiselect',
                'getData',
                'buildSearchCriteria',
                'getCategoriesTreeDept',
                'getLayer',
                'search',
                'hasCurrentValue'
            ])
            ->getMock();

        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchResult = $this->createMock(SearchResultInterface::class);
    }

    /**
     * @covers Category::getSearchResult
     *
     * @dataProvider getTestDatabase
     *
     * @throws \ReflectionException
     */
    public function testGetSearchResult($value, $expectedResult = null)
    {
        $this->model->expects($this->any())->method('getRenderCategoriesLevel')->willReturn(3);
        $this->model->expects($this->any())->method('getCategoriesTreeDept')->willReturn(1);
        $this->model->expects($this->any())->method('isRenderAllTree')->willReturn($value);
        $this->model->expects($this->any())->method('isMultiselect')->will($this->returnValue($value));
        $this->model->expects($this->any())->method('buildSearchCriteria')->will($this->returnValue($this->searchCriteria));
        $this->model->expects($this->any())->method('hasCurrentValue')->will($this->returnValue(true));

        $currentCategory = $this->getObjectManager()->getObject(\Magento\Catalog\Model\Category::class);
        $currentCategory->setData('id', 2);

        $layer = $this->createMock(\Magento\Catalog\Model\Layer::class);
        $layer->expects($this->any())->method('getCurrentCategory')->will($this->returnValue($currentCategory));
        $this->model->expects($this->any())->method('getLayer')->will($this->returnValue($layer));

        $search = $this->createMock(SearchInterface::class);
        $search->expects($this->any())->method('search')->will($this->returnValue($expectedResult));

        $rootCategory = $this->getObjectManager()->getObject(\Magento\Catalog\Model\Category::class);
        $rootCategory->setData('id', 1);
        $this->model->expects($this->any())->method('getData')->with('root_category')->will($this->returnValue($rootCategory));
        $this->setProperty($this->model, 'search', $search, Category::class);

        $resultOrigMethod = $this->invokeMethod($this->model, 'getSearchResult');
        $this->assertEquals($expectedResult, $resultOrigMethod);
    }

    /**
     * @return array
     */
    public function getTestDatabase()
    {
        return [
            [false],
            [true, $this->searchResult],
        ];
    }
}
