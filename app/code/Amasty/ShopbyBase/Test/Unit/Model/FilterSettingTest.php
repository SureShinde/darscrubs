<?php

namespace Amasty\ShopbyBase\Test\Unit\Model;

use Amasty\ShopbyBase\Model\FilterSetting;
use Amasty\ShopbyBase\Model\FilterSettingFactory;
use Amasty\ShopbyBase\Test\Unit\Traits;
use Amasty\ShopbySeo\Model\Source\IndexMode;

/**
 * Class FilterSettingTest
 *
 * @see FilterSetting
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class FilterSettingTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ReflectionTrait;
    use Traits\ObjectManagerTrait;

    /**
     * @var FilterSetting
     */
    private $model;

    /**
     * @var \Amasty\ShopbyBase\Model\FilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    private $attrModel;

    /**
     * @var FilterSettingFactory
     */
    private $filterSettings;

    public function setUp(): void
    {
        $this->model = $this->getObjectManager()->getObject(FilterSetting::class);
        $this->filterSettingFactory = $this
            ->createPartialMock(\Amasty\ShopbyBase\Model\FilterSettingFactory::class, ['create']);
        $this->attrModel = $this->createMock(\Magento\Eav\Model\Entity\Attribute::class);
        $this->filterSettings = $this->getMockBuilder(FilterSettingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @covers FilterSettingTest::getIndexMode
     *
     * @throws \ReflectionException
     */
    public function testGetIndexMode()
    {
        $this->assertEquals(0, $this->model->getIndexMode());
        $this->model->setData($this->model::INDEX_MODE, 'test_index_mode');
        $this->assertEquals('test_index_mode', $this->model->getIndexMode());
    }

    /**
     * @covers FilterSettingTest::getUnitsLabel
     *
     * @throws \ReflectionException
     */
    public function testGetUnitsLabel()
    {
        $this->assertEquals(null, $this->model->getUnitsLabel());
        $this->model->setData($this->model::USE_CURRENCY_SYMBOL, 'test');
        $this->assertEquals('test', $this->model->getUnitsLabel('test'));
    }

    /**
     * @covers FilterSetting::isAddNofollow
     *
     * @dataProvider isAddNofollowDataProvider
     *
     * @throws \ReflectionException
     */
    public function testIsAddNofollow($data, $expectedResult)
    {
        $mockFilterSettings = $this->createPartialMock(
            FilterSetting::class,
            [
                'getFollowMode',
                'isNofollowBySingleMode',
                'getShopbySeoHelper',
                'isPageNofollow',
                'getRelNofollow',
                'isNofollowByMode'
            ]
        );

        $enableRelNofollow = $this->createMock(\Amasty\ShopbySeo\Helper\Data::class);
        $enableRelNofollow->expects($this->any())->method('isEnableRelNofollow')->willReturn($data['isEnableRelNofollow']);
        $mockFilterSettings->expects($this->any())->method('getShopbySeoHelper')->willReturn($enableRelNofollow);
        $mockFilterSettings->expects($this->any())->method('isPageNofollow')->willReturn($data['isPageNofollow']);
        $mockFilterSettings->expects($this->any())->method('getRelNofollow')->willReturn($data['getRelNofollow']);
        $mockFilterSettings->expects($this->any())->method('isNofollowByMode')->willReturn($data['isNofollowByMode']);

        $actualResult = $mockFilterSettings->isAddNofollow();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @covers FilterSetting::isNofollowByMode
     *
     * @dataProvider isNofollowByModeDataProvider
     *
     * @throws \ReflectionException
     */
    public function testIsNofollowByMode($data, $expectedResult)
    {
        $mockFilterSettings = $this->createPartialMock(
            FilterSetting::class,
            [
                'getFollowMode',
                'isNofollowBySingleMode',
                'getShopbySeoHelper',
                'isPageNofollow',
                'getRelNofollow'
            ]
        );

        $mockFilterSettings->expects($this->any())->method('getFollowMode')->willReturn($data['followMode']);
        $mockFilterSettings
            ->expects($this->any())
            ->method('isNofollowBySingleMode')
            ->willReturn($data['isNofollowBySingleMode']);

        $actualResult = $mockFilterSettings->isNofollowByMode();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Data provider for isNofollowByModeDataProvider test
     * @return array
     */
    public function isNofollowByModeDataProvider()
    {
        return [
            [
                [
                    'followMode' => IndexMode::MODE_NEVER,
                    'isNofollowBySingleMode' => true
                ],
                true
            ],
            [
                [
                    'followMode' => IndexMode::MODE_SINGLE_ONLY,
                    'isNofollowBySingleMode' => true
                ],
                true
            ],
            [
                [
                    'followMode' => IndexMode::MODE_SINGLE_ONLY,
                    'isNofollowBySingleMode' => false
                ],
                false
            ],
            [
                [
                    'followMode' => IndexMode::MODE_ALWAYS,
                    'isNofollowBySingleMode' => true
                ],
                false
            ]
        ];
    }

    /**
     * Data provider for isAddNofollowDataProvider test
     * @return array
     */
    public function isAddNofollowDataProvider()
    {
        return [
            [
                [
                    'isEnableRelNofollow' => false,
                    'isPageNofollow' => false,
                    'getRelNofollow' => false,
                    'isNofollowByMode' => false
                ],
                false
            ],
            [
                [
                    'isEnableRelNofollow' => true,
                    'isPageNofollow' => true,
                    'getRelNofollow' => false,
                    'isNofollowByMode' => false
                ],
                false
            ],
            [
                [
                    'isEnableRelNofollow' => true,
                    'isPageNofollow' => false,
                    'getRelNofollow' => false,
                    'isNofollowByMode' => false
                ],
                false
            ],
            [
                [
                    'isEnableRelNofollow' => true,
                    'isPageNofollow' => false,
                    'getRelNofollow' => true,
                    'isNofollowByMode' => false
                ],
                false
            ],
            [
                [
                    'isEnableRelNofollow' => true,
                    'isPageNofollow' => false,
                    'getRelNofollow' => true,
                    'isNofollowByMode' => true
                ],
                true
            ]
        ];
    }
}
