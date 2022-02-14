<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\StateException;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\RequestInterface;

class IsNew extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    const FILTER_NEW = 1;
    const FILTER_NOT_NEW = 0;
    const FILTER_LABEL_XML_PATH = 'amshopby/am_is_new_filter/label';
    const FILTER_POSITION_XML_PATH = 'amshopby/am_is_new_filter/position';
    const ATTRIBUTE_CODE = 'am_is_new';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var FilterRequestDataResolver
     */
    private $filterRequestDataResolver;

    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        ItemDataBuilder $itemDataBuilder,
        ScopeConfigInterface $scopeConfig,
        SearchInterface $search,
        FilterRequestDataResolver $filterRequestDataResolver,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );

        $this->_requestVar = self::ATTRIBUTE_CODE;
        $this->scopeConfig = $scopeConfig;
        $this->search = $search;
        $this->filterRequestDataResolver = $filterRequestDataResolver;
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function apply(RequestInterface $request)
    {
        if ($this->filterRequestDataResolver->isApplied($this)) {
            return $this;
        }

        $value = $this->filterRequestDataResolver->getFilterParam($this);

        if (!in_array($value, [self::FILTER_NEW])) {
            return $this;
        }

        $this->filterRequestDataResolver->setCurrentValue($this, $value);

        if ($value == self::FILTER_NEW) {
            $name = __('Yes');
            $this->getLayer()->getProductCollection()->addFieldToFilter($this->getAttributeCode(), $value);
            $this->getLayer()->getState()->addFilter($this->_createItem($name, $value));
        }

        return $this;
    }

    /**
     * Get filter name
     *
     * @return string
     */
    public function getName()
    {
        return (string)$this->scopeConfig->getValue(
            self::FILTER_LABEL_XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return (int)$this->scopeConfig->getValue(
            self::FILTER_POSITION_XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->filterRequestDataResolver->isHidden($this)) {
            return [];
        }

        try {
            $optionsFacetedData = $this->getFacetedData();
        } catch (StateException $e) {
            $optionsFacetedData = [];
        }

        $newItemsCount = $this->countNewItems($optionsFacetedData);

        if ($newItemsCount > 0) {
            $this->itemDataBuilder->addItemData(__('New'), self::FILTER_NEW, $newItemsCount);
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * @param array $optionsFacetedData
     * @return mixed
     */
    private function countNewItems(array $optionsFacetedData)
    {
        return array_reduce($optionsFacetedData, function ($sum, $item) {
            return isset($item['count']) && $item['value'] != self::FILTER_NOT_NEW
                ? $sum + $item['count']
                : $sum;
        }, 0);
    }

    /**
     * @return string
     */
    private function getAttributeCode(): string
    {
        return self::ATTRIBUTE_CODE;
    }

    /**
     * @return array
     */
    private function getFacetedData(): array
    {
        $collection = $this->getLayer()->getProductCollection();

        return $collection->getFacetedData($this->getAttributeCode(), $this->getSearchResult());
    }
}
