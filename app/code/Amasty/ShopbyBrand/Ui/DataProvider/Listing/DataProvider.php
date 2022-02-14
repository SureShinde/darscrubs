<?php

namespace Amasty\ShopbyBrand\Ui\DataProvider\Listing;

use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Amasty\ShopbyBrand\Model\ResourceModel\Slider\Grid\Collection;
use Magento\Framework\Api\Filter;

class DataProvider extends AbstractDataProvider
{
    const VIRTUAL_STORE_ID = 'virtual_store_id';

    /**
     * @var array
     */
    private $mappedFields = [
        'meta_title' => ['main_table.meta_title', 'option.value'],
        'title' => ['main_table.title', 'option.value']
    ];

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        ConfigProvider $configProvider,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collection;
        $this->configProvider = $configProvider;
    }

    /**
     * TODO: Unit
     * @param Filter $filter
     * @return mixed|void
     */
    public function addFilter(Filter $filter)
    {
        $this->processVirtualStoreFilter($filter);
        $condition = [$filter->getConditionType() => $filter->getValue()];

        if (array_key_exists($filter->getField(), $this->mappedFields)) {
            $mappedFields = $this->mappedFields[$filter->getField()];
            $condition = array_fill(0, count($mappedFields), $condition);
            $filter->setField($mappedFields);
        }
        
        $this->getCollection()->addFieldToFilter(
            $filter->getField(),
            $condition
        );
    }

    public function processVirtualStoreFilter(Filter $filter): void
    {
        if ($filter->getField() == self::VIRTUAL_STORE_ID) {
            $filter->setField(FilterSettingInterface::FILTER_CODE);
            $allAttributeCodes = $this->configProvider->getAllBrandAttributeCodes();
            $value = (FilterSetting::ATTR_PREFIX . ($allAttributeCodes[$filter->getValue()] ?? '')) ?? '';
            $filter->setValue($value);
        }
    }

    /**
     * @return \int[]
     */
    public function getAllIds()
    {
        $this->getCollection();
        return parent::getAllIds();
    }
}
