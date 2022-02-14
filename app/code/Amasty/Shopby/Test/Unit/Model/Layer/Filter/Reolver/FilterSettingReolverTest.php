<?php

declare(strict_types=1);

namespace Amasty\Shopby\Test\Unit\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Attribute;
use Amasty\Shopby\Model\Layer\Filter\OnSale;
use Amasty\Shopby\Model\Layer\Filter\Price;
use Amasty\Shopby\Test\Unit\Traits;
use Amasty\ShopbyBase\Model\FilterSetting;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class FilterSettingReolverTest
 *
 * @see FilterSettingResolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class FilterSettingReolverTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var MockObject|FilterSettingResolver
     */
    private $model;

    public function setup(): void
    {
        $this->model = $this->getMockBuilder(FilterSettingResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFilterSetting'])
            ->getMock();
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsMultiselectAllowed(): void
    {
        $filterSetting = $this->createMock(FilterSetting::class);
        $this->model->expects($this->any())->method('getFilterSetting')->willReturn($filterSetting);

        $attributeFilter = $this->getObjectManager()->getObject(Attribute::class);
        $priceFilter = $this->getObjectManager()->getObject(Price::class);
        $onSaleFilter = $this->getObjectManager()->getObject(OnSale::class);

        $this->assertEquals(false, $this->invokeMethod($this->model, 'isMultiselectAllowed', [$onSaleFilter]));

        $this->assertEquals(true, $this->invokeMethod($this->model, 'isMultiselectAllowed', [$priceFilter]));

        $filterSetting->expects($this->any())->method('isMultiselect')->willReturn(true);
        $this->assertEquals(true, $this->invokeMethod($this->model, 'isMultiselectAllowed', [$attributeFilter]));
    }
}
