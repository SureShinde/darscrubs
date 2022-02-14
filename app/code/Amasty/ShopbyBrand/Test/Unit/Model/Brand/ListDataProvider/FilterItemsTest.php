<?php

namespace Amasty\ShopbyBrand\Test\Unit\Model\Brand\ListDataProvider;

use Amasty\ShopbyBrand\Model\Brand\BrandData;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems;
use Amasty\ShopbyBrand\Test\Unit\Traits;

/**
 * @covers FilterItems
 */
class FilterItemsTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;

    private $filterItems;

    public function setup(): void
    {
        $this->filterItems = $this->getObjectManager()->getObject(FilterItems::class);
    }

    /**
     * @dataProvider sortingItemsProvider
     * @covers SortItems::execute
     * @param BrandDataInterface[] $items
     * @param int $expectedCount
     * @param array $filterParams
     */
    public function testExecute(array $items, int $expectedCount, array $filterParams)
    {
        $result = $this->filterItems->execute($items, $filterParams);

        $this->assertCount($expectedCount, $result);
    }

    public function sortingItemsProvider()
    {
        $items = [
            $this->createItem(['is_show_in_slider' => false, 'is_show_in_widget' => true, 'cnt' => 1]),
            $this->createItem(['is_show_in_slider' => true, 'is_show_in_widget' => false, 'cnt' => 1]),
            $this->createItem(['is_show_in_slider' => true, 'is_show_in_widget' => true, 'cnt' => 1]),
            $this->createItem(['is_show_in_slider' => false, 'is_show_in_widget' => false, 'cnt' => 0]),
            $this->createItem(['is_show_in_slider' => true, 'is_show_in_widget' => true, 'cnt' => 0]),
            $this->createItem(['is_show_in_slider' => false, 'is_show_in_widget' => false, 'cnt' => '1']),
            $this->createItem(['is_show_in_slider' => true, 'is_show_in_widget' => true, 'cnt' => '1']),
            $this->createItem(['is_show_in_slider' => true, 'is_show_in_widget' => true, 'cnt' => '0']),
            $this->createItem(['is_show_in_slider' => 1, 'is_show_in_widget' => 1, 'cnt' => 1]),
            $this->createItem(['is_show_in_slider' => 0, 'is_show_in_widget' => 0, 'cnt' => 1]),
        ];

        return [
            'Filter for widget blocks' => [
                $items,
                4,
                [
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::FOR_WIDGET => true,
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::NOT_EMPTY => true,
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::FOR_SLIDER => false
                ]
            ],
            'Filter for slider blocks' => [
                $items,
                4,
                [
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::FOR_WIDGET => false,
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::NOT_EMPTY => true,
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::FOR_SLIDER => true
                ]
            ],
            'Filter for widget with empty allowed' => [
                $items,
                6,
                [
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::FOR_WIDGET => true,
                ]
            ],
            'Filter for not empty' => [
                $items,
                7,
                [
                    \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems::NOT_EMPTY => true,
                ]
            ],
        ];
    }

    private function createItem(array $data)
    {
        return $this->getObjectManager()->getObject(BrandData::class, ['data' => $data]);
    }
}
