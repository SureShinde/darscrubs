<?php

declare(strict_types=1);

namespace Amasty\Shopby\Test\Unit\Model\Layer;

use Amasty\Shopby\Helper\FilterSetting;
use Amasty\Shopby\Model\Layer\FilterList;
use Amasty\Shopby\Model\Source\FilterPlacedBlock;
use Amasty\Shopby\Test\Unit\Traits;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FilterListSortingTest extends TestCase
{
    use Traits\ReflectionTrait;

    /**
     * @var FilterList|MockObject
     */
    private $filterList;

    /**
     * @var FilterSetting|MockObject
     */
    private $filterHelper;

    /**
     * @covers       FilterList::sortingByPosition
     * @dataProvider sortingByPositionProvider
     */
    public function testSortingByPosition($itemsToSort, $resultItems, $place)
    {
        $this->setProperty($this->filterList, 'currentPlace', $place, FilterList::class);

        usort($itemsToSort, [$this->filterList, 'sortingByPosition']);

        self::assertSame($itemsToSort, $resultItems);
    }

    public function sortingByPositionProvider()
    {
        $item1 = $this->createFilter(1, FilterPlacedBlock::POSITION_SIDEBAR, 6, 6);
        $item2top5 = $this->createFilter(33, FilterPlacedBlock::POSITION_BOTH, 2, 5);
        $item3 = $this->createFilter(3, FilterPlacedBlock::POSITION_TOP, 0, 0);
        $item4top2 = $this->createFilter(0, FilterPlacedBlock::POSITION_BOTH, 4, 2);
        $item5top4 = $this->createFilter(1, FilterPlacedBlock::POSITION_BOTH, 4, 4);

        return [
            'Sidebar Sorting' => [
                [$item4top2, $item5top4, $item1, $item2top5, $item3],
                [$item1, $item2top5, $item3, $item4top2, $item5top4],
                \Amasty\Shopby\Model\Layer\FilterList::PLACE_SIDEBAR
            ],
            'Top Sorting' => [
                [$item4top2, $item5top4, $item1, $item2top5, $item3],
                [$item1, $item4top2, $item3, $item5top4, $item2top5],
                \Amasty\Shopby\Model\Layer\FilterList::PLACE_TOP
            ]
        ];
    }

    private function createFilter(int $position, int $blockPlace = 0, int $sidePosition = 0, int $topPosition = 0)
    {
        $filter = $this->getMockBuilder(\Amasty\Shopby\Model\Layer\Filter\OnSale::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPosition'])
            ->addMethods(['getSetting'])
            ->getMock();
        $filter->method('getPosition')->willReturn($position);

        $settings = $this->createMock(\Amasty\ShopbyBase\Model\FilterSetting::class);
        $settings->method('getTopPosition')->willReturn($topPosition);
        $settings->method('getSidePosition')->willReturn($sidePosition);
        $settings->method('getBlockPosition')->willReturn($blockPlace);

        $filter->method('getSetting')->willReturn($settings);

        return $filter;
    }

    protected function setUp(): void
    {
        $this->filterHelper = $this->createMock(FilterSetting::class);
        $this->filterHelper->method('getSettingByLayerFilter')
            ->willReturnCallback(
                function ($filter) {
                    return $filter->getSetting();
                }
            );

        $this->filterList = $this->getMockBuilder(FilterList::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->setProperty($this->filterList, 'filterSetting', $this->filterHelper, FilterList::class);
    }
}
