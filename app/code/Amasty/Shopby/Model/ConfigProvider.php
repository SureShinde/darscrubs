<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model;

class ConfigProvider extends \Amasty\Base\Model\ConfigProviderAbstract
{
    const TOOLTIPS_ENABLED = 'tooltips/enabled';
    const TOOLTIP_IMAGE = 'tooltips/image';
    const KEEP_SINGLE_CHOICE_VISIBLE = 'general/keep_single_choice_visible';
    const STOCK_SOURCE = 'stock_filter/stock_source';
    const STOCK_FILTER_ENABLED = 'stock_filter/enabled';
    const RATING_FILTER_ENABLED = 'rating_filter/enabled';
    const IS_NEW_FILTER_ENABLED = 'am_is_new_filter/enabled';
    const ON_SALE_FILTER_ENABLED = 'am_on_sale_filter/enabled';
    const EXCLUDE_OUT_OF_STOCK = 'general/exclude_out_of_stock';
    const BRAND_ATTRIBUTE_CODE = 'amshopby_brand/general/attribute_code';

    /**
     * @var string
     */
    protected $pathPrefix = 'amshopby/';

    public function isTooltipsEnabled(): string
    {
        return (string)$this->getValue(self::TOOLTIPS_ENABLED);
    }

    public function getTooltipSrc(): string
    {
        return (string)$this->getValue(self::TOOLTIP_IMAGE);
    }

    public function isSingleChoiceMode(): bool
    {
        return $this->isSetFlag(self::KEEP_SINGLE_CHOICE_VISIBLE);
    }

    public function getStockSource(): string
    {
        return $this->getValue(self::STOCK_SOURCE);
    }

    public function isStockFilterEnabled(): bool
    {
        return $this->isSetFlag(self::STOCK_FILTER_ENABLED);
    }

    public function isRatingFilterEnabled(): bool
    {
        return $this->isSetFlag(self::RATING_FILTER_ENABLED);
    }

    public function isNewFilterEnabled(): bool
    {
        return $this->isSetFlag(self::IS_NEW_FILTER_ENABLED);
    }

    public function isSaleFilterEnabled(): bool
    {
        return $this->isSetFlag(self::ON_SALE_FILTER_ENABLED);
    }

    public function getBrandAttributeCode(): string
    {
        return (string) $this->getGlobalValue(self::BRAND_ATTRIBUTE_CODE);
    }

    public function isExcludeOutOfStock(): bool
    {
        return (bool)$this->getValue(self::EXCLUDE_OUT_OF_STOCK);
    }

    public function getStockConfig(): array
    {
        return $this->getValue('stock_filter');
    }

    public function getRatingConfig(): array
    {
        return $this->getValue('rating_filter');
    }

    public function getNewConfig(): array
    {
        return $this->getValue('am_is_new_filter');
    }

    public function getOnSaleConfig(): array
    {
        return $this->getValue('am_on_sale_filter');
    }
}
