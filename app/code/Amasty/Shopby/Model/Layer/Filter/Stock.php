<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use \Magento\Store\Model\ScopeInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface as StockConfigurationInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\RequestInterface;

class Stock extends AbstractFilter
{
    const FILTER_DEFAULT = 0;

    const FILTER_IN_STOCK = 1;

    const FILTER_OUT_OF_STOCK = 2;

    const ATTRIBUTE_CODE = 'stock_status';

    const REQUEST_VAR = 'stock';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var int
     */
    private $filterOutStock = 0;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

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
        StockConfigurationInterface $stockConfiguration,
        SearchInterface $search,
        EngineResolverInterface $engineResolver,
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
        $this->_requestVar = 'stock';
        $this->scopeConfig = $scopeConfig;
        $this->stockConfiguration = $stockConfiguration;
        $this->search = $search;
        $this->filterRequestDataResolver = $filterRequestDataResolver;

        if ($engineResolver->getCurrentSearchEngine() !== Collection::MYSQL_ENGINE) {
            $this->filterOutStock = self::FILTER_OUT_OF_STOCK;
        }
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
        if (!in_array($value, [self::FILTER_IN_STOCK, self::FILTER_OUT_OF_STOCK])) {
            return $this;
        }

        $this->filterRequestDataResolver->setCurrentValue($this, $value);
        $isFilterOutOfStock = $value == self::FILTER_OUT_OF_STOCK;
        if ($this->isStockSourceQty()) {
            $qty = (float) $this->stockConfiguration->getMinQty($this->getStoreId());
            $qtyCondition = "IF({{table}}.use_config_min_qty, $qty, {{table}}.min_qty)";
            $condition = "{{table}}.stock_id = 1 AND (e.type_id != 'simple' OR {{table}}.qty > $qtyCondition)";
            if ($isFilterOutOfStock) {
                $condition = "{{table}}.stock_id = 1 AND e.type_id = 'simple' AND {{table}}.qty <= $qtyCondition";
            }

            $this->getLayer()
                ->getProductCollection()
                ->joinField(
                    'qty',
                    'cataloginventory_stock_item',
                    'qty',
                    'product_id = entity_id',
                    $condition,
                    'inner'
                );
        } else {
            $applyFilter = $isFilterOutOfStock ? $this->filterOutStock : self::FILTER_IN_STOCK;
            $this->getLayer()->getProductCollection()->addFieldToFilter($this->getAttributeCode(), $applyFilter);
        }

        $name = $isFilterOutOfStock ? __('Out of Stock'): __('In Stock');
        $this->getLayer()->getState()->addFilter($this->_createItem($name, $value));
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
            ->getValue('amshopby/stock_filter/label', ScopeInterface::SCOPE_STORE);
        return $label;
    }

    public function getPosition()
    {
        $position = (int) $this->scopeConfig
            ->getValue('amshopby/stock_filter/position', ScopeInterface::SCOPE_STORE);
        return $position;
    }

    /**
     * @return bool
     */
    private function isStockSourceQty()
    {
         $stockSource = $this->scopeConfig
            ->getValue('amshopby/stock_filter/stock_source', ScopeInterface::SCOPE_STORE);
        return $stockSource === \Amasty\Shopby\Model\Source\StockFilterSource::QTY;
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

        $inStock = isset($optionsFacetedData[self::FILTER_IN_STOCK])
            ? $optionsFacetedData[self::FILTER_IN_STOCK]['count'] : 0;
        $outStock = isset($optionsFacetedData[$this->filterOutStock])
            ? $optionsFacetedData[$this->filterOutStock]['count'] : 0;

        $listData = [
            [
                'label' => __('In Stock'),
                'value' => self:: FILTER_IN_STOCK,
                'count' => $inStock,
            ],
            [
                'label' => __('Out of Stock'),
                'value' => self:: FILTER_OUT_OF_STOCK,
                'count' => $outStock,
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

    private function getAttributeCode(): ?string
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
    
    private function getSearchResult(): ?SearchResultInterface
    {
        $alteredQueryResponse = null;
        if ($this->filterRequestDataResolver->hasCurrentValue($this)) {
            $searchCriteria = $this->getLayer()->getProductCollection()->getSearchCriteria([$this->getAttributeCode()]);
            $alteredQueryResponse = $this->search->search($searchCriteria);
        }

        return $alteredQueryResponse;
    }
}
