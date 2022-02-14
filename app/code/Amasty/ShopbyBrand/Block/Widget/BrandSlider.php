<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Block\Widget;

use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems;
use Amasty\ShopbyBrand\Model\Source\SliderSort;
use Magento\Widget\Block\BlockInterface;

class BrandSlider extends BrandListAbstract implements BlockInterface
{
    const HTML_ID = 'amslider_id';

    const DEFAULT_ITEM_NUMBER = 4;

    const DEFAULT_IMG_WIDTH = 130;

    /**
     * deprecated. used for back compatibility.
     */
    const CONFIG_VALUES_PATH = 'amshopby_brand/slider';

    /**
     * @var  array|null
     */
    protected $items;

    /**
     * @return array
     */
    public function getSliderOptions()
    {
        $options = [];
        $itemsPerView = max(1, $this->getItemNumber());
        $options['slidesPerView'] = $itemsPerView;
        $options['loop'] = $this->getData('infinity_loop') ? 'true' : 'false';
        $options['simulateTouch'] = $this->getData('simulate_touch') ? 'true' : 'false';
        if ($this->getData('pagination_show')) {
            $options['pagination'] = '".swiper-pagination"';
            $options['paginationClickable'] = 'true';
        }

        if ($this->getData('autoplay')) {
            $options['autoplay'] = (int)$this->getData('autoplay_delay');
        }

        return $options;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!count($this->getItems())) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return array
     */
    public function getItems()
    {
        if ($this->items === null) {
            $storeId = (int) $this->_storeManager->getStore()->getId();
            $this->items = $this->brandListDataProvider->getList(
                $storeId,
                $this->getItemsFilter(),
                $this->getData('sort_by') ?? SliderSort::NAME
            );
        }

        return $this->items;
    }

    private function getItemsFilter(): array
    {
        $filters = [
            FilterItems::FOR_SLIDER => true
        ];

        if (!$this->isDisplayZero()) {
            $filters[FilterItems::NOT_EMPTY] = true;
        }

        return $filters;
    }

    public function getHeaderColor(): string
    {
        return (string) $this->getData('slider_header_color');
    }

    public function getTitleColor(): string
    {
        return (string) $this->getData('slider_title_color');
    }

    public function getTitle(): string
    {
        return (string) $this->getData('slider_title');
    }

    public function getItemNumber(): int
    {
        return (int) $this->getData('items_number') ?: self::DEFAULT_ITEM_NUMBER;
    }

    public function isSliderEnabled(): bool
    {
        return count($this->getItems()) > $this->getItemNumber();
    }

    protected function getConfigValuesPath(): string
    {
        return self::CONFIG_VALUES_PATH;
    }
}
