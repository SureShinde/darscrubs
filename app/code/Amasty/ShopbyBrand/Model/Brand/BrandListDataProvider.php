<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Brand;

class BrandListDataProvider
{
    /**
     * @var ListDataProvider\LoadItems
     */
    private $loadItems;

    /**
     * @var ListDataProvider\SortItems
     */
    private $sortItems;

    /**
     * @var ListDataProvider\FilterItems
     */
    private $filterItems;

    public function __construct(
        ListDataProvider\LoadItems $loadItems,
        ListDataProvider\SortItems $sortItems,
        ListDataProvider\FilterItems $filterItems
    ) {
        $this->loadItems = $loadItems;
        $this->sortItems = $sortItems;
        $this->filterItems = $filterItems;
    }

    /**
     * @param int $storeId
     * @param array $filterParams where keys is a filter names
     * @param string|null $sortBy
     *
     * @return BrandDataInterface[]
     */
    public function getList(int $storeId, array $filterParams = [], ?string $sortBy = null): array
    {
        $items = $this->loadItems->getItems($storeId);

        if (!empty($filterParams)) {
            $items = $this->filterItems->execute($items, $filterParams);
        }
        if (!empty($sortBy)) {
            $items = $this->sortItems->execute($items, $sortBy);
        }

        return $items;
    }
}
