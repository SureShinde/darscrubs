<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter\Resolver;

use Amasty\Shopby\Helper\FilterSetting as FilterSettingHelper;
use Amasty\Shopby\Model\ConfigProvider;
use Amasty\Shopby\Model\Layer\Filter\Decimal;
use Amasty\Shopby\Model\Layer\Filter\IsNew;
use Amasty\Shopby\Model\Layer\Filter\OnSale;
use Amasty\Shopby\Model\Layer\Filter\Price;
use Amasty\Shopby\Model\Layer\Filter\Rating;
use Amasty\Shopby\Model\Layer\Filter\Stock;
use Amasty\Shopby\Model\Source\PositionLabel;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Model\FilterSetting;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class FilterSettingResolver
{
    /**
     * @var FilterSettingInterface[]
     */
    private $filterSetting = [];

    /**
     * @var FilterSettingHelper
     */
    private $settingHelper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(FilterSettingHelper $settingHelper, ConfigProvider $configProvider)
    {
        $this->settingHelper = $settingHelper;
        $this->configProvider = $configProvider;
    }

    public function isMultiselectAllowed(FilterInterface $filter): bool
    {
        switch (true) {
            case $filter instanceof Stock:
            case $filter instanceof Rating:
            case $filter instanceof OnSale:
            case $filter instanceof IsNew:
                $isMultiselectAllowed = false;
                break;
            case $filter instanceof Price:
            case $filter instanceof Decimal:
                $isMultiselectAllowed = true;
                break;
            default:
                $isMultiselectAllowed = $this->getFilterSetting($filter)->isMultiselect();
        }

        return $isMultiselectAllowed;
    }

    public function getFilterSetting(FilterInterface $filter): FilterSettingInterface
    {
        if (!isset($this->filterSetting[$filter->getRequestVar()])) {
            $this->filterSetting[$filter->getRequestVar()] = $this->settingHelper->getSettingByLayerFilter($filter);
        }

        return $this->filterSetting[$filter->getRequestVar()];
    }

    public function isSingleChoiceMode(): bool
    {
        return $this->configProvider->isSingleChoiceMode();
    }
}
