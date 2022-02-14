<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterConfigResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterSettingResolver as DecimalFilterSettingResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterRequestDataResolver as DecimalFilterRequestDataResolver;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Amasty\Shopby\Model\Source\DisplayMode;
use Amasty\Shopby\Api\Data\FromToFilterInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory;
use Magento\Framework\Search\Dynamic\Algorithm;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as PriceResource;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use \Magento\Catalog\Model\Layer\Filter\DataProvider\Price as PriceDataProvider;
use \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory as PriceDataProviderFactory;
use Magento\Framework\App\RequestInterface;

class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price implements FromToFilterInterface
{
    const AM_BASE_PRICE = 'am_base_price';
    const INVALID_DATA_COUNT = 1;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var PriceDataProvider
     */
    private $dataProvider;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var FilterRequestDataResolver
     */
    private $filterRequestDataResolver;

    /**
     * @var FilterSettingResolver
     */
    private $filterSettingResolver;

    /**
     * @var DecimalFilterRequestDataResolver
     */
    private $decimalFilterRequestDataResolver;

    /**
     * @var DecimalFilterSettingResolver
     */
    private $decimalFilterSettingResolver;

    /**
     * @var FilterConfigResolver
     */
    private $filterConfigResolver;

    /**
     * @var array
     */
    private $facetedData = null;

    /**
     * @var int
     */
    private $range = 0;

    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        ItemDataBuilder $itemDataBuilder,
        PriceResource $resource,
        CustomerSession $customerSession,
        Algorithm $priceAlgorithm,
        PriceCurrencyInterface $priceCurrency,
        AlgorithmFactory $algorithmFactory,
        PriceDataProviderFactory $dataProviderFactory,
        SearchInterface $search,
        ManagerInterface $messageManager,
        FilterRequestDataResolver $filterRequestDataResolver,
        FilterSettingResolver $filterSettingResolver,
        DecimalFilterSettingResolver $decimalFilterSettingResolver,
        DecimalFilterRequestDataResolver $decimalFilterRequestDataResolver,
        FilterConfigResolver $filterConfigResolver,
        array $data = []
    ) {
        $this->dataProvider = $dataProviderFactory->create(['layer' => $layer]);
        $this->priceCurrency = $priceCurrency;
        $this->search = $search;
        $this->messageManager = $messageManager;
        $this->filterRequestDataResolver = $filterRequestDataResolver;
        $this->filterSettingResolver = $filterSettingResolver;
        $this->decimalFilterRequestDataResolver = $decimalFilterRequestDataResolver;
        $this->decimalFilterSettingResolver = $decimalFilterSettingResolver;
        $this->filterConfigResolver = $filterConfigResolver;
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getFromToConfig(): array
    {
        return $this->filterConfigResolver->getConfig($this, $this->getFacetedData());
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function _getItemsData()
    {
        if ($this->filterRequestDataResolver->isHidden($this, true)) {
            return [];
        }
        
        $facets = $this->getFacetedData();

        $data = [];
        if (count($facets) > self::INVALID_DATA_COUNT) { // two range minimum
            foreach ($facets as $key => $aggregation) {
                $count = $aggregation['count'];
                if (strpos($key, '_') === false) {
                    continue;
                }
                $data[] = $this->prepareData($key, $count);
            }
        }

        return count($data) == self::INVALID_DATA_COUNT ? [] : $data;
    }

    /**
     * @param string $key
     * @param int $count
     * @return array
     */
    private function prepareData($key, $count)
    {
        [$from, $to] = explode('_', $key);
        if ($from == '*') {
            $from = $this->getFrom($to);
        }

        if (!$this->range) {
            if (in_array($from, [0, '*']) && $to != '*') {
                $this->range = $to;
            } elseif ($to != '*') {
                $this->range = $to - $from;
            }
        }

        if ($to == '*') {
            $to = $this->range ? $from + $this->range : '';
        }

        $label = $this->renderRangeLabel(
            empty($from) ? 0 : $from,
            $to
        );

        $value = sprintf('%.2f-%.2f%s', $from, $to, $this->dataProvider->getAdditionalRequestData());
        $data = [
            'label' => $label,
            'value' => $value,
            'count' => $count,
            'from' => $from,
            'to' => $to,
        ];

        return $data;
    }

    /**
     * @param RequestInterface $request
     * @return $this
     */
    public function apply(RequestInterface $request)
    {
        if ($this->filterRequestDataResolver->isApplied($this)) {
            return $this;
        }

        $filter = $this->filterRequestDataResolver->getFilterParam($this);
        $noValidate = false;

        if (!empty($filter) && is_string($filter)) {
            $filter = explode('-', $filter);

            $toValue = isset($filter[1]) && $filter[1] ? $filter[1] : '';
            $filter = $filter[0] . '-' . $toValue;
            $validateFilter = $this->decimalFilterRequestDataResolver->getValidFilterValue($filter);

            if (!$validateFilter) {
                $noValidate = true;
            } else {
                $this->decimalFilterRequestDataResolver->setFromTo(
                    $this,
                    (float) $validateFilter[0],
                    (float) $validateFilter[1]
                );
            }
        }

        if ($noValidate || !$filter) {
            return $this;
        }
        $this->applyFilter($validateFilter ?? $filter);

        if (!empty($filter) && !is_array($filter)) {
            $filterSetting = $this->filterSettingResolver->getFilterSetting($this);
            if ($filterSetting->getDisplayMode() == DisplayMode::MODE_SLIDER) {
                $this->getLayer()->getProductCollection()->addFieldToFilter('price', $filter);
            }
        }

        return $this;
    }

    private function applyFilter(array $filter)
    {
        list($from, $to) = $filter;

        $this->getLayer()->getProductCollection()->addFieldToFilter(
            'price',
            ['from' => $from, 'to' => $to]
        );

        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
        );
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getFacetedData(): array
    {
        if ($this->facetedData === null) {
            $productCollection = $this->getLayer()->getProductCollection();
            try {
                $this->facetedData = $productCollection->getFacetedData(
                    $this->getAttributeModel()->getAttributeCode(),
                    $this->getSearchResult()
                );
            } catch (StateException $e) {
                if (!$this->messageManager->hasMessages()) {
                    $this->messageManager->addErrorMessage(
                        __(
                            'Make sure that "%1" attribute can be used in layered navigation',
                            $this->getAttributeModel()->getAttributeCode()
                        )
                    );
                }

                $this->facetedData = [];
            }
        }

        return $this->facetedData;
    }

    /**
     * @param float|string $fromPrice
     * @param float|string $toPrice
     * @return string|\Magento\Framework\Phrase
     */
    protected function renderRangeLabel($fromPrice, $toPrice)
    {
        $delta = $this->decimalFilterRequestDataResolver->getDelta($this);
        $fromPrice = $this->decimalFilterSettingResolver->calculatePrice($this, (float) $fromPrice, $delta);

        if (!$toPrice) {
            $toPrice = 0;
        } else {
            $delta = $this->decimalFilterRequestDataResolver->getDelta($this, false);
            $toPrice = $this->decimalFilterSettingResolver->calculatePrice($this, (float) $toPrice, $delta);
        }

        return $this->renderLabelDependOnPrice((float) $fromPrice, (float) $toPrice);
    }

    /**
     * method is used for Amasty\GroupedOptions\Plugin\Shopby\Model\Layer\Filter\Price plugin
     * @param float $fromPrice
     * @param float $toPrice
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function renderLabelDependOnPrice(float $fromPrice, float $toPrice)
    {
        $formattedFromPrice = $this->priceCurrency->format($fromPrice);
        if (!$toPrice) {
            return __('%1 and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice && $this->dataProvider->getOnePriceIntervalValue()) {
            return $formattedFromPrice;
        } else {
            return __('%1 - %2', $formattedFromPrice, $this->priceCurrency->format($toPrice));
        }
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        $itemsCount = $this->decimalFilterSettingResolver->isIgnoreRanges($this) ? 0 : parent::getItemsCount();

        if ($itemsCount == 0) {
            /**
             * show up filter event don't have any option
             */
            $fromToConfig = $this->getFromToConfig();
            if ($fromToConfig && $fromToConfig['min'] != $fromToConfig['max']) {
                return 1;
            }
        }

        return $itemsCount;
    }

    private function getSearchResult(): ?SearchResultInterface
    {
        $alteredQueryResponse = null;
        if ($this->filterRequestDataResolver->hasCurrentValue($this)) {
            $searchCriteria = $this->getLayer()->getProductCollection()->getSearchCriteria([
                $this->getAttributeModel()->getAttributeCode() . '.from',
                $this->getAttributeModel()->getAttributeCode() . '.to'
            ]);
            $alteredQueryResponse = $this->search->search($searchCriteria);
        }

        return $alteredQueryResponse;
    }
}
