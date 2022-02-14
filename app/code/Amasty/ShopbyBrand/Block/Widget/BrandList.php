<?php

namespace Amasty\ShopbyBrand\Block\Widget;

use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems;
use Amasty\ShopbyBrand\Model\Source\SliderSort;
use Amasty\ShopbyBrand\Model\Source\Tooltip;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class BrandList extends BrandListAbstract implements BlockInterface
{
    /**
     * deprecated. leave for back compatibility.
     */
    const CONFIG_VALUES_PATH = 'amshopby_brand/brands_landing';

    /**
     * @var  array|null
     */
    protected $items;

    /**
     * @return array
     */
    public function getIndex()
    {
        $items = $this->getItems();
        if (!$items) {
            return [];
        }

        $letters = $this->sortByLetters($items);
        $index = $this->breakByColumns($letters);

        return $index;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function sortByLetters($items)
    {
        $letters = $this->items2letters($items);

        return $letters;
    }

    /**
     * @param array $letters
     *
     * @return array
     */
    private function breakByColumns($letters)
    {
        $columnCount = abs((int)$this->getData('columns'));
        if (!$columnCount) {
            $columnCount = 1;
        }

        $row = 0; // current row
        $num = 0; // current number of items in row
        $index = [];
        foreach ($letters as $letter => $items) {
            $index[$row][$letter] = $items['items'];
            $num++;
            if ($num >= $columnCount) {
                $num = 0;
                $row++;
            }
        }

        return $index;
    }

    /**
     * @return BrandDataInterface[]
     */
    public function getItems()
    {
        if ($this->items === null) {
            $storeId = (int) $this->_storeManager->getStore()->getId();
            $this->items = $this->brandListDataProvider->getList($storeId, $this->getItemsFilter(), SliderSort::NAME);
        }

        return $this->items;
    }

    private function getItemsFilter(): array
    {
        $filters = [
            FilterItems::FOR_WIDGET => true
        ];

        if (!$this->isDisplayZero()) {
            $filters[FilterItems::NOT_EMPTY] = true;
        }

        return $filters;
    }

    /**
     * @param array $items
     * @return array
     */
    protected function items2letters($items)
    {
        $letters = [];
        foreach ($items as $item) {
            $letter = $this->getLetter($item['label']);
            if (!isset($letters[$letter]['items'])) {
                $letters[$letter]['items'] = [];
            }

            $letters[$letter]['items'][] = $item;
            if (!isset($letters[$letter]['count'])) {
                $letters[$letter]['count'] = 0;
            }

            $letters[$letter]['count']++;
        }

        return $letters;
    }

    /**
     * @param $item
     * @return false|mixed|string|string[]|null
     */
    public function getLetter($label)
    {
        if (function_exists('mb_strtoupper')) {
            $letter = mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'));
        } else {
            $letter = strtoupper(substr($label, 0, 1));
        }

        if (is_numeric($letter)) {
            $letter = '#';
        }

        return $letter;
    }

    /**
     * @return array
     */
    public function getAllLetters()
    {
        $brandLetters = [];
        /** @codingStandardsIgnoreStart */
        foreach ($this->getIndex() as $letters) {
            $brandLetters = array_merge($brandLetters, array_keys($letters));
        }
        /** @codingStandardsIgnoreEnd */

        return $brandLetters;
    }

    /**
     * @return string
     */
    public function getSearchHtml()
    {
        $html = '';
        if (!$this->isShowSearch() || !$this->getItems()) {
            return $html;
        }

        $searchCollection = [];
        foreach ($this->getItems() as $item) {
            $searchCollection[$item['url']] = $item['label'];
        }

        /** @var Template $block */
        $block = $this->getSearchBrandBlock();
        if ($block) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $searchCollection = json_encode($searchCollection, JSON_HEX_APOS);
            $block->setBrands($searchCollection);
            $html = $block->toHtml();
        }

        return $html;
    }

    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getSearchBrandBlock()
    {
        $block = $this->getLayout()->getBlock('ambrands.search');
        if (!$block) {
            $block = $this->getLayout()->createBlock(Template::class, 'ambrands.search')
                ->setTemplate('Amasty_ShopbyBrand::brand_search.phtml');
        }

        return $block;
    }

    public function isTooltipEnabled(): bool
    {
        $setting = $this->helper->getModuleConfig('general/tooltip_enabled');

        return in_array(Tooltip::ALL_BRAND_PAGE, explode(',', $setting));
    }

    public function getTooltipAttribute(BrandDataInterface $item): string
    {
        if ($this->isTooltipEnabled()) {
            $result = $this->helper->generateToolTipContent($item);
        }

        return $result ?? '';
    }

    public function getImageWidth(): int
    {
        return abs((int) $this->getData('image_width')) ?: 100;
    }

    public function getImageHeight(): int
    {
        return abs((int) $this->getData('image_height'));
    }

    public function isShowBrandLogo(): bool
    {
        return (bool) $this->getData('show_images');
    }

    public function isShowSearch(): bool
    {
        return (bool) $this->getData('show_search');
    }

    public function isShowFilter(): bool
    {
        return (bool) $this->getData('show_filter');
    }

    public function isFilterDisplayAll(): bool
    {
        return (bool) $this->getData('filter_display_all');
    }

    public function isShowCount(): bool
    {
        return (bool) $this->getData('show_count');
    }

    protected function getConfigValuesPath(): string
    {
        return self::CONFIG_VALUES_PATH;
    }
}
