<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal;

use Amasty\Shopby\Model\Layer\Filter\Price;
use Amasty\Shopby\Model\Source\DisplayMode;
use Amasty\Shopby\Model\Source\PositionLabel;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver as DefaultFilterSettingResolver;
use Magento\Store\Model\StoreManagerInterface;

class FilterSettingResolver
{
    const DEFAULT_CURRENCY_RATE = 1;
    const NUMBERS_AFTER_POINT = 2;

    /**
     * @var string
     */
    private $currencySymbol;

    /**
     * @var DefaultFilterSettingResolver
     */
    private $settingResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        DefaultFilterSettingResolver $settingResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->currencySymbol = $priceCurrency->getCurrencySymbol();
        $this->settingResolver = $settingResolver;
        $this->storeManager = $storeManager;
    }

    public function getUseSliderOrFromTo(FilterInterface $filter): bool
    {
        $filterSetting = $this->settingResolver->getFilterSetting($filter);
        return $this->isFromToDisplayMode((int) $filterSetting->getDisplayMode()) ||
            (bool)$filterSetting->getAddFromToWidget();
    }

    public function getSliderTemplate(FilterInterface $filter): string
    {
        $labelPosition = $this->getCurrencyPosition($filter);
        $labelUnit = $this->getCurrencySymbol($filter);

        if ($labelPosition == PositionLabel::POSITION_BEFORE) {
            $template = $labelUnit . '{from}' . ' - ' . $labelUnit . '{to}';
        } else {
            $template = '{from}' . $labelUnit . ' - {to}' . $labelUnit;
        }

        return $template;
    }

    public function getCurrencySymbol(FilterInterface $filter): ?string
    {
        $filterSetting = $this->settingResolver->getFilterSetting($filter);
        return $filterSetting->getUnitsLabelUseCurrencySymbol()
            ? $this->currencySymbol
            : $filterSetting->getUnitsLabel();
    }

    public function getCurrencyPosition(FilterInterface $filter): int
    {
        $filterSetting = $this->settingResolver->getFilterSetting($filter);
        if ($filterSetting->getUnitsLabelUseCurrencySymbol()) {
            /** @var PriceCurrencyInterface $priceCurrency */
            $priceCurrency = \Magento\Framework\App\ObjectManager::getInstance()->get(PriceCurrencyInterface::class);
            $trialValue = '345';

            //label position can be customized by "currency_display_options_forming" event. Trigger it.
            $formattedExample = $priceCurrency->format($trialValue, false, 0);

            $labelPosition = strpos($formattedExample, $trialValue) !== 0
                ? PositionLabel::POSITION_BEFORE
                : PositionLabel::POSITION_AFTER;
        } else {
            $labelPosition = $filterSetting->getPositionLabel();
        }

        return $labelPosition;
    }

    public function isIgnoreRanges(FilterInterface $filter): bool
    {
        $filterSetting = $this->settingResolver->getFilterSetting($filter);

        return $this->isFromToDisplayMode((int) $filterSetting->getDisplayMode());
    }

    public function getCurrencyRate(FilterInterface $filter): float
    {
        $rate = self::DEFAULT_CURRENCY_RATE;
        if ($filter instanceof Price) {
            $rate = $this->storeManager->getStore($filter->getStoreId())
                ->getCurrentCurrencyRate();
        }

        return (float) $rate;
    }

    public function calculatePrice(FilterInterface $filter, float $price, float $delta): float
    {
        if ($this->getCurrencyRate($filter) == self::DEFAULT_CURRENCY_RATE) {
            return $price;
        }

        return round($price * $this->getCurrencyRate($filter) + $delta, self::NUMBERS_AFTER_POINT);
    }

    private function isFromToDisplayMode(?int $displayMode): bool
    {
        return in_array($displayMode, [DisplayMode::MODE_SLIDER, DisplayMode::MODE_FROM_TO_ONLY]);
    }
}
