<?php

declare(strict_types=1);

namespace Amasty\Shopby\Test\Unit\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Attribute;
use Amasty\Shopby\Model\Layer\Filter\OnSale;
use Amasty\Shopby\Model\Layer\Filter\Price;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterConfigResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterRequestDataResolver as DecimalFilterRequestDataResolover;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterSettingResolver as DecimalFilterSettingResolver;
use Amasty\Shopby\Test\Unit\Traits;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Model\FilterSetting;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class FilterConfigResolverTest
 *
 * @see FilterSettingResolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class FilterConfigResolverTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var MockObject|FilterConfigResolver
     */
    private $model;

    /**
     * @var MockObject|FilterSettingResolver
     */
    private $settingResolver;

    /**
     * @var MockObject|DecimalFilterSettingResolver
     */
    private $decimalSettingResolver;

    /**
     * @var MockObject|DecimalFilterRequestDataResolover
     */
    private $decimalFilterRequestDataResolver;

    public function setup(): void
    {
        $this->settingResolver = $this->createMock(FilterSettingResolver::class);
        $this->decimalSettingResolver = $this->createMock(DecimalFilterSettingResolver::class);
        $this->decimalFilterRequestDataResolver = $this->createMock(DecimalFilterRequestDataResolover::class);

        $this->decimalSettingResolver->expects($this->any())->method('getCurrencySymbol')->willReturn('$');
        $this->decimalSettingResolver->expects($this->any())->method('getCurrencyPosition')->willReturn(0);
        $this->decimalSettingResolver->expects($this->any())->method('getSliderTemplate')->willReturn('');
        $this->decimalSettingResolver->expects($this->any())->method('getCurrencyRate')->willReturn((float) 1);

        $this->decimalFilterRequestDataResolver->expects($this->any())->method('getDelta')
            ->willReturn((float) 0);

        $this->model = $this->getObjectManager()->getObject(
            FilterConfigResolver::class,
            [
                'settingResolver' => $this->settingResolver,
                'decimalSettingResolver' => $this->decimalSettingResolver,
                'decimalFilterRequestDataResolver' => $this->decimalFilterRequestDataResolver,
            ]
        );
    }

    /**
     *
     * @covers FilterConfigResolver::getConfig
     *
     * @dataProvider getConfigDataProvider
     *
     * @param $facetedData
     * @param $useSlider
     * @param $sliderData
     * @param $fromToData
     * @param $result
     * @throws \ReflectionException
     */
    public function testGetConfig($facetedData, $useSlider, $sliderData, $fromToData, $result): void
    {
        $filterSetting = $this->getObjectManager()->getObject(FilterSetting::class);
        $filterSetting->addData($sliderData);
        $filterSetting->setSliderStep(1);

        $this->settingResolver->expects($this->any())->method('getFilterSetting')->willReturn($filterSetting);

        $this->decimalSettingResolver->expects($this->any())->method('getUseSliderOrFromTo')->willReturn($useSlider);

        $this->decimalFilterRequestDataResolver->expects($this->any())->method('getCurrentFrom')->willReturn((float) $fromToData['from']);
        $this->decimalFilterRequestDataResolver->expects($this->any())->method('getCurrentTo')->willReturn((float) $fromToData['to']);

        $filter = $this->createMock(Attribute::class);

        $methodResult = $this->invokeMethod($this->model, 'getConfig', [$filter, $facetedData]);

        $this->assertEquals($result['from'], $methodResult['from']);
        $this->assertEquals($result['to'], $methodResult['to']);
        $this->assertEquals($result['min'], $methodResult['min']);
        $this->assertEquals($result['max'], $methodResult['max']);
    }

    /**
     * DataProvider for testGetConfig
     *
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            [
                ['data' => ['min' => '0', 'max' => '0']],
                false,
                [FilterSettingInterface::SLIDER_MIN => '5', FilterSettingInterface::SLIDER_MAX => '25'],
                ['from' => 20, 'to' => 30],
                ['min' => null, 'max' => null, 'from' => null, 'to' => null]
            ],
            [
                ['data' => ['count' => 1, 'min' => '10', 'max' => '50']],
                false,
                [FilterSettingInterface::SLIDER_MIN => '5', FilterSettingInterface::SLIDER_MAX => '25'],
                ['from' => 20, 'to' => 30],
                ['min' => null, 'max' => null, 'from' => null, 'to' => null]
            ],
            [
                ['data' => ['count' => 5, 'min' => '10', 'max' => '50']],
                true,
                [FilterSettingInterface::SLIDER_MIN => '5', FilterSettingInterface::SLIDER_MAX => '60'],
                ['from' => 20, 'to' => 30],
                ['min' => 10, 'max' => 50, 'from' => 20, 'to' => 30]
            ],
            [
                ['data' => ['count' => 5, 'min' => '10', 'max' => '50']],
                true,
                [FilterSettingInterface::SLIDER_MIN => '20', FilterSettingInterface::SLIDER_MAX => '40'],
                ['from' => 20, 'to' => 30],
                ['min' => 20, 'max' => 40, 'from' => 20, 'to' => 30]
            ],
            [
                ['data' => ['count' => 5, 'min' => '10', 'max' => '50']],
                true,
                [FilterSettingInterface::SLIDER_MIN => '20', FilterSettingInterface::SLIDER_MAX => '40'],
                ['from' => 0, 'to' => 100],
                ['min' => 20, 'max' => 100, 'from' => '', 'to' => 100]
            ]
        ];
    }
}
