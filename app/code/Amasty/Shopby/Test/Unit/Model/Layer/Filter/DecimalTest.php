<?php

namespace Amasty\Shopby\Test\Unit\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Decimal;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterConfigResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterSettingResolver as DecimalFilterSettingResolver;
use Amasty\Shopby\Test\Unit\Traits;
use Amasty\ShopbyBase\Model\FilterSetting;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchResultInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;

/**
 * Class DecimalTest
 *
 * @see Decimal
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class DecimalTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var Decimal
     */
    private $model;

    /**
     * @var MockObject|FilterSettingResolver
     */
    private $filterSettingResolver;

    /**
     * @var MockObject|FilterRequestDataResolver
     */
    private $filterRequestDataResolver;

    /**
     * @var MockObject|FilterConfigResolver
     */
    private $decimalFilterConfigResolver;

    /**
     * @var MockObject|DecimalFilterSettingResolver
     */
    private $decimalFilterSettingResolver;

    /**
     * @var MockObject|\Amasty\Shopby\Model\ResourceModel\Fulltext\Collection
     */
    private $productCollection;

    public function setup(): void
    {
        $this->filterSettingResolver = $this->createMock(FilterSettingResolver::class);
        $this->filterRequestDataResolver = $this->createMock(FilterRequestDataResolver::class);
        $this->decimalFilterSettingResolver = $this->createMock(DecimalFilterSettingResolver::class);
        $this->decimalFilterConfigResolver = $this->createMock(FilterConfigResolver::class);
        $filterItemFactory = $this->getMockBuilder(\Magento\Catalog\Model\Layer\Filter\ItemFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $filterItem = $this->getMockBuilder(\Magento\Catalog\Model\Layer\Filter\Item::class)
            ->setMethods(['setFilter', 'setLabel', 'setValue', 'setCount'])
            ->disableOriginalConstructor()
            ->getMock();
        $attributeModel = $this->createMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        $attributeModel->expects($this->any())->method('getAttributeCode')->willReturn('test');
        $search = $this->createMock(\Magento\Search\Api\SearchInterface::class);
        $layer = $this->createMock(\Magento\Catalog\Model\Layer::class);
        $this->productCollection = $this->createMock(\Amasty\Shopby\Model\ResourceModel\Fulltext\Collection::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResult = $this->createMock(SearchResultInterface::class);
        $priceCurrency = $this->createMock(\Magento\Framework\Pricing\PriceCurrencyInterface::class);
        $messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->setMethods(['hasMessages', 'addErrorMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $filterItemFactory->expects($this->any())->method('create')->willReturn($filterItem);
        $filterItem->expects($this->any())->method('setFilter')->willReturn($filterItem);
        $filterItem->expects($this->any())->method('setLabel')->willReturn($filterItem);
        $filterItem->expects($this->any())->method('setValue')->willReturn($filterItem);
        $filterItem->expects($this->any())->method('setValue')->willReturn($filterItem);
        $search->expects($this->any())->method('search')->willReturn($searchResult);
        $layer->expects($this->any())->method('getProductCollection')->willReturn($this->productCollection);
        $this->productCollection->expects($this->any())->method('getSearchCriteria')->willReturn($searchCriteria);
        $messageManager->expects($this->any())->method('hasMessages')->willReturn(true);
        $messageManager->expects($this->any())->method('addErrorMessage')->willReturn(true);
        $priceCurrency->expects($this->any())->method('format')->willReturnArgument(0);

        $this->model = $this->getObjectManager()->getObject(
            Decimal::class,
            [
                'filterSettingResolver' => $this->filterSettingResolver,
                'filterRequestDataResolver' => $this->filterRequestDataResolver,
                'decimalConfigResolver' => $this->decimalFilterConfigResolver,
                'decimalFilterSettingResolver' => $this->decimalFilterSettingResolver,
                'filterItemFactory' => $filterItemFactory,
                'search' => $search,
                'messageManager' => $messageManager,
                'priceCurrency' => $priceCurrency,
            ]
        );

        $this->model->setAttributeModel($attributeModel);
        $this->setProperty($this->model, '_catalogLayer', $layer);
    }

    /**
     * @covers Decimal::getItemsCountIfNotIgnoreRanges
     */
    public function testGetItemsCountIfNotIgnoreRanges()
    {
        $settingFilter = $this->getObjectManager()->getObject(FilterSetting::class);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);
        $this->setProperty($this->model, 'facetedData', ['10_20' => ['count' => 2]]);

        $this->assertEquals(1, $this->model->getItemsCount());
    }

    /**
     * @covers Decimal::getItemsCountIfIgnoreRanges
     */
    public function testGetItemsCountIfIgnoreRanges()
    {
        $data = [
            'data' =>['count' => 1, 'min' => 1, 'max' => 2],
            '10_20' => ['count' => 2]
        ];
        $settingFilter = $this->getObjectManager()->getObject(FilterSetting::class);
        $settingFilter->setDisplayMode(3);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);
        $this->decimalFilterConfigResolver->expects($this->any())->method('getConfig')
            ->willReturnOnConsecutiveCalls($this->returnValue([]), $this->returnValue(['min' => 1, 'max' => 2]));

        $this->setProperty($this->model, 'facetedData', ['data' =>['count' => 1, 'min' => 0, 'max' => 0]]);
        $this->assertEquals(0, $this->model->getItemsCount());

        $this->setProperty($this->model, 'facetedData', $data);
        $this->assertEquals(1, $this->model->getItemsCount());
    }


    /**
     * @covers Decimal::getItemsData
     */
    public function testGetItemsData()
    {
        $data = [
            'data' =>['count' => 1, 'min' => 1, 'max' => 2],
            '10_20' => ['count' => 2]
        ];
        $this->setProperty($this->model, 'facetedData', ['data' => 1]);
        $this->assertEquals([], $this->invokeMethod($this->model, '_getItemsData'));
        $settingFilter = $this->createMock(FilterSetting::class);
        $settingFilter->expects($this->any())->method('getUnitsLabelUseCurrencySymbol')->willReturn(true);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);

        $this->setProperty($this->model, 'facetedData', $data);
        $result = $this->invokeMethod($this->model, '_getItemsData');
        $this->assertEquals('10.00-20.00', $result[0]['value']);
        $this->assertEquals('2', $result[0]['count']);
        $this->assertEquals('10', $result[0]['from']);
        $this->assertEquals('20', $result[0]['to']);
    }

    /**
     * @covers Decimal::getSearchResult
     */
    public function testGetSearchResult()
    {
        $this->assertNull($this->invokeMethod($this->model, 'getSearchResult'));
        $this->filterRequestDataResolver->expects($this->any())->method('hasCurrentValue')->willReturn(true);
        $this->assertInstanceOf(SearchResultInterface::class, $this->invokeMethod($this->model, 'getSearchResult'));
    }

    /**
     * @covers Decimal::getFacetedData
     */
    public function testGetFacetedData()
    {
        $this->setProperty($this->model, 'magentoVersion', '2.4.2');
        $this->productCollection->expects($this->any())->method('getFacetedData')->willReturn(['test1', 'test2']);
        $this->assertEquals(['test1', 'test2'], $this->invokeMethod($this->model, 'getFacetedData'));

        $this->setProperty($this->model, 'facetedData', ['test']);
        $this->assertEquals(['test'], $this->invokeMethod($this->model, 'getFacetedData'));
    }

    /**
     * @covers Decimal::getFacetedDataException
     */
    public function testGetFacetedDataException()
    {
        $this->setProperty($this->model, 'magentoVersion', '2.4.2');
        $this->productCollection->expects($this->any())->method('getFacetedData')
            ->willThrowException(new \Magento\Framework\Exception\StateException(__('exceprion')));
        $this->assertEquals([], $this->invokeMethod($this->model, 'getFacetedData'));
    }

    /**
     * @covers Decimal::getDefaultRangeLabel
     */
    public function testGetDefaultRangeLabel()
    {
        $settingFilter = $this->createMock(FilterSetting::class);
        $settingFilter->expects($this->any())->method('getUnitsLabelUseCurrencySymbol')->willReturn(true);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);
        $this->filterRequestDataResolver->expects($this->any())->method('hasCurrentValue')->willReturn(true);

        $this->assertEquals(
            '10 - 19.99',
            (string)$this->invokeMethod($this->model, 'getDefaultRangeLabel', [10.0, 20.0])
        );

        $this->assertEquals(
            '0 - 19.99',
            (string)$this->invokeMethod($this->model, 'getDefaultRangeLabel', [0, 20.0])
        );
    }

    /**
     * @covers Decimal::getDefaultRangeLabelWithoutData
     */
    public function testGetDefaultRangeLabelWithoutData()
    {
        $settingFilter = $this->createMock(FilterSetting::class);
        $settingFilter->expects($this->any())->method('getUnitsLabelUseCurrencySymbol')->willReturn(false);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);

        $this->assertEquals(
            '',
            (string)$this->invokeMethod($this->model, 'getDefaultRangeLabel', [10.0, 20.0])
        );
    }

    /**
     * @covers Decimal::getRangeForState
     */
    public function testGetRangeForState()
    {
        $settingFilter = $this->getObjectManager()->getObject(FilterSetting::class);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);

        $this->decimalFilterSettingResolver->expects($this->any())->method('getUseSliderOrFromTo')
            ->willReturnOnConsecutiveCalls(false, false, true, true);

        $settingFilter->setPositionLabel(0);
        $settingFilter->setData('units_label', '$');

        $this->assertEquals(
            '$10.00 - $20.00',
            (string)$this->invokeMethod($this->model, 'getRangeLabel', [10.0, 20.0])
        );

        $settingFilter->setPositionLabel(1);
        $settingFilter->setData('units_label', '$');
        $this->assertEquals(
            '10.00$ - 20.00$',
            (string)$this->invokeMethod($this->model, 'getRangeLabel', [10.0, 20.0])
        );

        $this->assertEquals(
            '10.00$ and above',
            (string)$this->invokeMethod($this->model, 'getRangeLabel', [10.0, (float) 0])
        );

        $settingFilter->setDisplayMode(\Amasty\Shopby\Model\Source\DisplayMode::MODE_SLIDER);
        $this->assertEquals(
            '10.00$ - 20.00$',
            (string)$this->invokeMethod($this->model, 'getRangeLabel', [10.0, 20.0])
        );

        $settingFilter->setDisplayMode(\Amasty\Shopby\Model\Source\DisplayMode::MODE_FROM_TO_ONLY);
        $this->assertEquals(
            '10.00$ - 20.00$',
            (string)$this->invokeMethod($this->model, 'getRangeLabel', [10.0, 20.0])
        );
    }

    /**
     * @covers Decimal::formatLabelForStateAndRange
     */
    public function testFormatLabelForStateAndRange()
    {
        $settingFilter = $this->getObjectManager()->getObject(FilterSetting::class);
        $this->filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);

        $settingFilter->setPositionLabel(0);
        $settingFilter->setData('units_label', '$');
        $this->assertEquals(
            '$10.00',
            $this->invokeMethod($this->model, 'formatLabelForStateAndRange', ['10'])
        );
        $settingFilter->setPositionLabel(1);
        $settingFilter->setData('units_label', '$');
        $this->assertEquals(
            '10.00$',
            $this->invokeMethod($this->model, 'formatLabelForStateAndRange', ['10'])
        );
    }
}
