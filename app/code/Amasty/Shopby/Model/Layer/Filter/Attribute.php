<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Amasty\ShopbyBase\Model\OptionSettingRepository;
use Magento\Catalog\Model\Layer;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filter\StripTags as TagFilter;
use Magento\Catalog\Model\Layer\Filter\ItemFactory as FilterItemFactory;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Amasty\Shopby\Model\Search\RequestGenerator as ShopbyRequestGenerator;
use Magento\Store\Model\Store;
use Magento\Framework\App\RequestInterface;
use Amasty\Shopby\Model\Source\SortOptionsBy;
use Magento\Framework\Message\ManagerInterface as MessageManager;

class Attribute extends AbstractFilter
{
    /**
     * @var TagFilter
     */
    private $tagFilter;

    /**
     * @var FilterSettingInterface
     */
    private $filterSetting;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var  ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var OptionSettingRepository
     */
    private $optionSettingRepository;

    /**
     * @var MessageManager
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

    public function __construct(
        FilterItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        ItemDataBuilder $itemDataBuilder,
        TagFilter $tagFilter,
        SearchInterface $search,
        ScopeConfigInterface $scopeConfig,
        OptionSettingRepository $optionSettingRepository,
        MessageManager $messageManager,
        FilterRequestDataResolver $filterRequestDataResolver,
        FilterSettingResolver $filterSettingResolver,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );

        $this->tagFilter = $tagFilter;
        $this->scopeConfig = $scopeConfig;
        $this->search = $search;
        $this->optionSettingRepository = $optionSettingRepository;
        $this->messageManager = $messageManager;
        $this->filterRequestDataResolver = $filterRequestDataResolver;
        $this->filterSettingResolver = $filterSettingResolver;
    }

    /**
     * Apply attribute option filter to product collection.
     *
     * @param RequestInterface $request
     * @return $this
     * @throws LocalizedException
     */

    public function apply(RequestInterface $request)
    {
        if ($this->filterRequestDataResolver->isApplied($this)) {
            return $this;
        }

        $requestedOptionsString = $this->filterRequestDataResolver->getFilterParam($this);

        if (empty($requestedOptionsString)) {
            return $this;
        }

        $requestedOptions = explode(',', $requestedOptionsString);

        $this->filterRequestDataResolver->setCurrentValue($this, $requestedOptions);
        $this->addState($requestedOptions);

        if (!$this->filterSettingResolver->isMultiSelectAllowed($this) && count($requestedOptions) > 1) {
            $requestedOptions = array_slice($requestedOptions, 0, 1);
        }

        $attribute = $this->getAttributeModel();

        /** @var \Amasty\Shopby\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        if ($this->filterSettingResolver->getFilterSetting($this)->isUseAndLogic()) {
            foreach ($requestedOptions as $key => $value) {
                $fakeAttributeCode = $this->getFakeAttributeCodeForApply($attribute->getAttributeCode(), $key);
                $productCollection->addFieldToFilter($fakeAttributeCode, $value);
            }
        } else {
            $productCollection->addFieldToFilter($attribute->getAttributeCode(), $requestedOptions);
        }

        return $this;
    }

    /**
     * @param array $values
     * @throws LocalizedException
     */
    private function addState(array $values)
    {
        if (!$this->shouldAddState()) {
            return;
        }

        foreach ($values as $value) {
            $item = $this->_createItem($this->getOptionText($value), $value);
            $this->getLayer()->getState()
                ->addFilter(
                    $item
                );
        }
    }

    /**
     * @return bool
     */
    public function shouldAddState()
    {
        // Could be overwritten in plugins.
        return true;
    }

    /**
     * @param string $attributeCode
     * @param $key
     * @return string
     */
    private function getFakeAttributeCodeForApply($attributeCode, $key)
    {
        if ($key > 0) {
            $attributeCode .= ShopbyRequestGenerator::FAKE_SUFFIX . $key;
        }

        return $attributeCode;
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->getItems());
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public function sortOption($a, $b)
    {
        $pattern = '@^(\d+)@';
        if (preg_match($pattern, $a['label'], $ma) && preg_match($pattern, $b['label'], $mb)) {
            $r = $ma[1] - $mb[1];
            if ($r != 0) {
                return $r;
            }
        }

        return strcasecmp($a['label'], $b['label']);
    }

    /**
     * Get data array for building attribute filter items.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        if ($this->filterRequestDataResolver->getFilterParam($this)
            && !$this->filterRequestDataResolver->isVisibleWhenSelected($this)
        ) {
            return [];
        }

        $options = $this->getOptions();
        $optionsFacetedData = $this->getOptionsFacetedData();

        if (!$optionsFacetedData) {
            return [];
        }

        $this->addItemsToDataBuilder($options, $optionsFacetedData);

        $itemsData = $this->getItemsFromDataBuilder();

        $this->sortItemsByFeatured($itemsData);
        return $itemsData;
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        $attribute = $this->getAttributeModel();
        $options = $attribute->getFrontend()->getSelectOptions();

        if ($this->filterSettingResolver->getFilterSetting($this)->getSortOptionsBy() == SortOptionsBy::NAME) {
            usort($options, [$this, 'sortOption']);
        }

        return $options;
    }

    /**
     * Additional Sort options by is_featured setting
     */
    private function sortItemsByFeatured(array &$options): array
    {
        $attribute = $this->getAttributeModel();
        $filterCode = \Amasty\Shopby\Helper\FilterSetting::ATTR_PREFIX . $attribute->getAttributeCode();
        $featuredOptionArray = [];
        $nonFeaturedOptionArray = [];
        $featuredOptions = $this->optionSettingRepository->getAllFeaturedOptionsArray($this->getStoreId());
        foreach ($options as $option) {
            if ($this->isOptionFeatured($featuredOptions, $filterCode, $option)) {
                $featuredOptionArray[] = $option;
            } else {
                $nonFeaturedOptionArray[] = $option;
            }
        }
        $options = array_merge($featuredOptionArray, $nonFeaturedOptionArray);
        $filterSetting = $this->filterSettingResolver->getFilterSetting($this);
        if (count($featuredOptionArray)
            && count($nonFeaturedOptionArray)
            && !$filterSetting->getNumberUnfoldedOptions()
        ) {
            $filterSetting->setNumberUnfoldedOptions(count($featuredOptionArray));
        }

        return $options;
    }

    /**
     * @param array $options
     * @param string $filterCode
     * @param array $option
     * @return bool
     */
    private function isOptionFeatured($options, $filterCode, $option)
    {
        return isset($options[$filterCode][$option['value']][$this->getStoreId()])
            || isset($options[$filterCode][$option['value']][Store::DEFAULT_STORE_ID]);
    }

    /**
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOptionsFacetedData()
    {
        $optionsFacetedData = $this->generateOptionsFacetedData();

        if (count($optionsFacetedData)) {
            $optionsFacetedData = $this->convertOptionsFacetedData($optionsFacetedData);
        }

        return $optionsFacetedData;
    }

    /**
     * @param array $optionsFacetedData
     *
     * @return array
     */
    protected function convertOptionsFacetedData($optionsFacetedData)
    {
        $attributeValue = $this->filterRequestDataResolver->getFilterParam($this);
        if ($attributeValue) {
            $values = explode(',', $attributeValue);
            foreach ($values as $value) {
                if (!empty($value) && !array_key_exists($value, $optionsFacetedData)) {
                    $optionsFacetedData[$value] = ['value' => $value, 'count' => 0];
                }
            }
        }

        return $optionsFacetedData;
    }

    /**
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateOptionsFacetedData()
    {
        /** @var \Amasty\Shopby\Model\ResourceModel\Fulltext\Collection $productCollectionOrigin */
        $productCollectionOrigin = $this->getLayer()->getProductCollection();
        $attribute = $this->getAttributeModel();

        try {
            $optionsFacetedData = $productCollectionOrigin->getFacetedData(
                $attribute->getAttributeCode(),
                $this->getSearchResult()
            );
        } catch (StateException $e) {
            if (!$this->messageManager->hasMessages()) {
                $this->messageManager->addErrorMessage(
                    __(
                        'Make sure that "%1" attribute can be used in layered navigation',
                        $attribute->getAttributeCode()
                    )
                );
            }
            $optionsFacetedData = [];
        }

        return $optionsFacetedData;
    }

    private function getSearchResult(): ?SearchResultInterface
    {
        $searchResult = null;

        if ($this->filterRequestDataResolver->hasCurrentValue($this)
            && !$this->filterSettingResolver->getFilterSetting($this)->isUseAndLogic()
        ) {
            $searchCriteria = $this->getLayer()->getProductCollection()
                ->getSearchCriteria([$this->getAttributeModel()->getAttributeCode()]);
            $searchResult = $this->search->search($searchCriteria);
        }

        return $searchResult;
    }

    /**
     * @param array $options
     * @param array $optionsFacetedData
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addItemsToDataBuilder($options, $optionsFacetedData)
    {
        if (!$options) {
            return;
        }
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }

            $isFilterableAttribute = $this->getAttributeIsFilterable($this->getAttributeModel());
            if (isset($optionsFacetedData[$option['value']])
                || $isFilterableAttribute != static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
            ) {
                $count = 0;
                if (isset($optionsFacetedData[$option['value']]['count'])) {
                    $count = $optionsFacetedData[$option['value']]['count'];
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($option['label']),
                    $option['value'],
                    $count
                );
            }
        }
    }

    /**
     * Get items data according to attribute settings.
     * @return array
     */
    private function getItemsFromDataBuilder()
    {
        $itemsData = $this->itemDataBuilder->build();
        if (count($itemsData) == 1
            && !$this->isOptionReducesResults(
                $itemsData[0]['count'],
                $this->getLayer()->getProductCollection()->getSize()
            )
        ) {
            $itemsData = $this->filterRequestDataResolver->getReducedItemsData($this, $itemsData);
        }

        return $itemsData;
    }
}
