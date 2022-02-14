<?php

namespace Amasty\ShopbyBrand\Test\Unit\Model\Brand\ListDataProvider;

use Amasty\ShopbyBrand\Model\Brand\BrandData;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\SortItems;
use Amasty\ShopbyBrand\Model\Source\SliderSort;
use Amasty\ShopbyBrand\Test\Unit\Traits;

/**
 * @covers SortItems
 */
class SortItemsTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    private $sortItems;

    public function setup(): void
    {
        $this->sortItems = $this->getObjectManager()->getObject(SortItems::class);
    }

    /**
     * @dataProvider sortingItemsProvider
     * @covers SortItems::execute
     * @param BrandDataInterface[] $items key is expected position
     * @param string $mode
     */
    public function testExecute(array $items, string $mode)
    {
        $result = $this->sortItems->execute($items, $mode);
        ksort($items);
        $this->assertSame($items, $result);
    }

    public function sortingItemsProvider()
    {
        return [
            'Sorting By Label' => [
                [// key is expected position
                    0 => $this->createItem(['label' => '10']),
                    3 => $this->createItem(['label' => 'Aa b']),
                    1 => $this->createItem(['label' => '9 Z']),
                    4 => $this->createItem(['label' => 'C Latin']),
                    2 => $this->createItem(['label' => 'Aa a']),
                    5 => $this->createItem(['label' => 'С Cyrillic']),
                ],
                SliderSort::NAME
            ],
            'Sorting By Position' => [
                [
                    2 => $this->createItem(['position' => '2']),
                    1 => $this->createItem(['position' => 1]),
                    3 => $this->createItem(['position' => 2.01]),
                    5 => $this->createItem(['position' => 9999]),
                    0 => $this->createItem(['position' => -500]),
                    4 => $this->createItem(['position' => 2.02]),
                ],
                SliderSort::POSITION
            ]
        ];
    }

    private function createItem(array $data)
    {
        return $this->getObjectManager()->getObject(BrandData::class, ['data' => $data]);
    }
}
