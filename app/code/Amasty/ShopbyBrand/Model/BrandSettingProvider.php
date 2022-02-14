<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBase\Model\OptionSetting;
use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\CollectionFactory;
use Amasty\ShopbyBrand\Helper\Data;

class BrandSettingProvider
{
    /**
     * @var array
     */
    private $brandSettingsByStore = [];

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Data
     */
    private $brandHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        Data $brandHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->brandHelper = $brandHelper;
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getItemsByStoreId(int $storeId): array
    {
        if (!isset($this->brandSettingsByStore[$storeId])) {
            $this->brandSettingsByStore[$storeId] = [];
            $attributeCode = $this->brandHelper->getBrandAttributeCode();

            if ($attributeCode) {
                $filterCode = FilterSetting::ATTR_PREFIX . $attributeCode;
                $stores = [0,  $storeId];
                $collection = $this->collectionFactory->create()
                    ->addFieldToFilter('store_id', $stores)
                    ->addFieldToFilter('filter_code', $filterCode)
                    ->addOrder('store_id', 'ASC'); //current store values will rewrite defaults

                /** @var OptionSettingInterface $item **/
                foreach ($collection as $item) {
                    $this->brandSettingsByStore[$storeId][$item->getValue()] = $item;
                }
            }
        }

        return $this->brandSettingsByStore[$storeId];
    }

    /**
     * @param int $storeId
     * @param int $value
     *
     * @return OptionSetting|null
     */
    public function getItemByStoreIdAndValue(int $storeId, int $value): ?OptionSetting
    {
        return $this->getItemsByStoreId($storeId)[$value] ?? null;
    }
}
