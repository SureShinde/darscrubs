<?php

namespace Amasty\Shopby\Test\Unit\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Attribute;
use Amasty\Shopby\Test\Unit\Traits;
use Amasty\ShopbyBase\Model\FilterSetting;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Search\Api\SearchInterface;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;

/**
 * Class BrandsPopupTest
 *
 * @see Attribute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class AttributeTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    const GROUP_CODE = 'grCode';

    const OPTIONS_ARRAY = [
        8  => ['value' => '8' , 'count' => '6'],
        11 => ['value' => '11', 'count' => '9'],
        18 => ['value' => '18', 'count' => '4'],
        19 => ['value' => '19', 'count' => '5'],
        20 => ['value' => '20', 'count' => '6'],
        21 => ['value' => '21', 'count' => '3'],
        22 => ['value' => '22', 'count' => '6'],
        23 => ['value' => '23', 'count' => '5'],
    ];

    /**
     * @covers Attribute::getSearchResult
     *
     * @throws \ReflectionException
     */
    public function testGetSearchResult()
    {
        $filterSettingResolver = $this->createMock(FilterSettingResolver::class);
        $filterRequestDataResolver = $this->createMock(FilterRequestDataResolver::class);
        $search = $this->createPartialMock(SearchInterface::class, ['search']);
        $layer = $this->createMock(\Magento\Catalog\Model\Layer::class);
        $model = $this->getObjectManager()->getObject(
            Attribute::class,
            [
                'filterSettingResolver' => $filterSettingResolver,
                'filterRequestDataResolver' => $filterRequestDataResolver,
                'search' => $search,
                '_catalogLayer' => $layer,
            ]
        );
        $this->assertNull($this->invokeMethod($model, 'getSearchResult'));

        $settingFilter = $this->getObjectManager()->getObject(FilterSetting::class);

        $productCollection = $this->createMock(\Amasty\Shopby\Model\ResourceModel\Fulltext\Collection::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResult = $this->createMock(SearchResultInterface::class);
        $attributeModel = $this->createMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        $filterSettingResolver->expects($this->any())->method('getFilterSetting')->willReturn($settingFilter);

        $search->expects($this->any())->method('search')->willReturn($searchResult);
        $layer->expects($this->any())->method('getProductCollection')->willReturn($productCollection);
        $productCollection->expects($this->any())->method('getSearchCriteria')->willReturn($searchCriteria);
        $attributeModel->expects($this->any())->method('getAttributeCode')->willReturn('test');
        $filterRequestDataResolver->expects($this->any())->method('hasCurrentValue')->willReturn(true);

        $model->setData('attribute_model', $attributeModel);

        $this->assertInstanceOf(SearchResultInterface::class, $this->invokeMethod($model, 'getSearchResult'));
    }
}
