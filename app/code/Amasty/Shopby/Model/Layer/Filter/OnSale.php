<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Magento\Catalog\Model\Layer;
use Magento\Framework\Exception\StateException;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;

class OnSale extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    const FILTER_ON_SALE = 1;

    const ATTRIBUTE_CODE = 'am_on_sale';

    /**
     * @var ScopeConfigInterface
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
        if (!in_array($value, [self::FILTER_ON_SALE])) {
            return $this;
        }

        $this->filterRequestDataResolver->setCurrentValue($this, $value);
        if ($value == self::FILTER_ON_SALE) {
            $this->getLayer()->getProductCollection()->addFieldToFilter($this->getAttributeCode(), $value);
            $name = __('Yes');
            $this->getLayer()->getState()->addFilter($this->_createItem($name, $value));
        }

        return $this;
    }

    /**
     * Get filter name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getName()
    {
        $label = $this->scopeConfig
            ->getValue('amshopby/am_on_sale_filter/label', ScopeInterface::SCOPE_STORE);
        return $label;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        $position = (int) $this->scopeConfig
            ->getValue('amshopby/am_on_sale_filter/position', ScopeInterface::SCOPE_STORE);
        return $position;
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

        $isOnSale = isset($optionsFacetedData[1]) ? $optionsFacetedData[1]['count'] : 0;

        $listData = [
            [
                'label' => __('On Sale'),
                'value' => self::FILTER_ON_SALE,
                'count' => $isOnSale,
            ]
        ];

        foreach ($listData as $data) {
            if ($data['count'] < 1) {
                continue;
            }
            $this->itemDataBuilder->addItemData(
                $data['label'],
                $data['value'],
                $data['count']
            );
        }

        return $this->itemDataBuilder->build();
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
