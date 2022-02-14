<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection as ShopbyFulltextCollection;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Amasty\Shopby\Model\Source\RenderCategoriesLevel;
use Amasty\Shopby\Helper\Category as CategoryHelper;
use Amasty\Shopby\Model\Source\CategoryTreeDisplayMode;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\Flat\Collection as CategoryFlatCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Layer;
use Magento\Search\Api\SearchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Filter\ItemFactory as FilterItemFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;
use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory as CategoryDataProviderFactory;
use Amasty\ShopbyBase\Model\Category\Manager as CategoryManager;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Amasty\Shopby\Model\Layer\Filter\Item\CategoryExtendedDataBuilder;
use Amasty\Shopby\Helper\Data as ShopbyHelper;

class Category extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter
{
    const MIN_CATEGORY_DEPTH = 1;

    const DENY_PERMISSION = '-2';

    const FILTER_FIELD = 'category';

    const EXCLUDE_CATEGORY_FROM_FILTER = 'am_exclude_from_filter';

    const TRUE = 1;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var CategoryDataProvider
     */
    private $dataProvider;

    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Item\CategoryExtendedDataBuilder
     */
    private $categoryExtendedDataBuilder;

    /**
     * @var CategoryItemsFactory
     */
    private $categoryItemsFactory;

    /**
     * @var ShopbyHelper
     */
    private $helper;

    /**
     * @var CategoryHelper
     */
    private $categoryHelper;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var FilterRequestDataResolver
     */
    private $filterRequestDataResolver;

    /**
     * @var FilterSettingResolver
     */
    private $filterSettingResolver;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(
        FilterItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        Escaper $escaper,
        CategoryDataProviderFactory $categoryDataProviderFactory,
        CategoryManager $categoryManager,
        CategoryRepositoryInterface $categoryRepository,
        CategoryExtendedDataBuilder $categoryExtendedDataBuilder,
        CategoryItemsFactory $categoryItemsFactory,
        ShopbyHelper $helper,
        CategoryHelper $categoryHelper,
        SearchInterface $search,
        MessageManager $messageManager,
        ProductMetadataInterface $productMetadata,
        FilterRequestDataResolver $filterRequestDataResolver,
        FilterSettingResolver $filterSettingResolver,
        CategoryCollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->_requestVar = 'cat';
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->categoryManager = $categoryManager;
        $this->categoryRepository = $categoryRepository;
        $this->categoryExtendedDataBuilder = $categoryExtendedDataBuilder;
        $this->categoryItemsFactory = $categoryItemsFactory;
        $this->categoryHelper = $categoryHelper;
        $this->search = $search;
        $this->messageManager = $messageManager;
        $this->productMetadata = $productMetadata;
        $this->filterRequestDataResolver = $filterRequestDataResolver;
        $this->filterSettingResolver = $filterSettingResolver;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Apply category filter to product collection
     *
     * @param   RequestInterface $request
     * @return  $this
     */
    public function apply(RequestInterface $request)
    {
        if ($this->filterRequestDataResolver->isApplied($this)) {
            return $this;
        }
        $categoryId = $this->filterRequestDataResolver->getFilterParam($this) ?: $request->getParam('id');
        if (empty($categoryId)) {
            return $this;
        }

        $categoryIds = explode(',', $categoryId);
        $categoryIds = array_unique($categoryIds);
        $category = $this->dataProvider->getCategory();
        if ($this->isMultiselect() && $request->getParam('id') != $categoryId) {
            $categoryIds = $this->excludeCategoriesFromFilter($categoryIds);
            if (empty($categoryIds)) {
                return $this;
            }

            $this->filterRequestDataResolver->setCurrentValue($this, $categoryIds);
            $child = $category->getCollection()
                ->addFieldToFilter($category->getIdFieldName(), ['in' => $categoryIds])
                ->addAttributeToSelect('name');
            $categoriesInState = [];
            foreach ($categoryIds as $categoryId) {
                if ($currentCategory = $child->getItemById($categoryId)) {
                    $categoriesInState[$currentCategory->getId()] = $currentCategory->getName();
                }
            }
            foreach ($categoriesInState as $key => $category) {
                $state = $this->_createItem($category, $key);
                $this->getLayer()->getState()->addFilter($state);
            }
        } else {
            $this->filterRequestDataResolver->setCurrentValue($this, $categoryIds);
            $this->dataProvider->setCategoryId($categoryId);
            if ($request->getParam('id') != $category->getId() && $this->dataProvider->isValid()) {
                $this->getLayer()->getState()->addFilter(
                    $this->_createItem(
                        $this->dataProvider->getCategory()->getName(),
                        $categoryId
                    )
                );
            }
        }
        /** @var ShopbyFulltextCollection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFieldToFilter(CategoryHelper::ATTRIBUTE_CODE, $categoryIds);

        return $this;
    }

    private function excludeCategoriesFromFilter(array $categoryIds): array
    {
        $collection = $this->getExcludedCategoryCollection();
        $excluded = $collection ? $collection->getColumnValues($collection->getEntity()->getIdFieldName()) : [];

        return array_values(array_diff($categoryIds, $excluded));
    }

    /**
     * Get filter value for reset current filter state
     *
     * @return mixed|null
     */
    public function getResetValue()
    {
        return $this->dataProvider->getResetValue();
    }

    /**
     * Get filter name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getName()
    {
        return __('Category');
    }

    /**
     * Get fiter items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        if (!$this->categoryHelper->isCategoryFilterExtended()) {
            return count($this->getItems()->getItems(null));
        }

        return $this->getItems()->getCount();
    }

    /**
     * @return $this|\Magento\Catalog\Model\Layer\Filter\AbstractFilter
     * @throws LocalizedException
     */
    protected function _initItems()
    {
        /** @var CategoryItems $itemsCollection */
        $itemsCollection = $this->categoryItemsFactory->create();
        $data = $this->getExtendedCategoryData();
        if ($data && ($data['count'] > 1 || !$this->isMultiselect())) {
            $itemsCollection->setStartPath($data['startPath']);
            $itemsCollection->setCount($data['count']);
            foreach ($data['items'] as $path => $items) {
                foreach ($items as $itemData) {
                    $itemsCollection->addItem(
                        $path,
                        $this->_createItem($itemData['label'], $itemData['value'], $itemData['count'])
                    );
                }
            }
        }

        $this->_items = $itemsCollection;

        return $this;
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $optionsFacetedData = $this->getFacetedData();
        $category = $this->dataProvider->getCategory();
        $categories = $category->getChildrenCategories();

        if ($categories instanceof CategoryCollection) {
            $categories->addAttributeToSelect('thumbnail');
        }

        if ($category->getIsActive()) {
            foreach ($categories as $category) {
                if ($category->getIsActive()
                    && $category->getIsAnchor()
                    && isset($optionsFacetedData[$category->getId()])
                ) {
                    $this->itemDataBuilder->addItemData(
                        $this->escaper->escapeHtml($category->getName()),
                        $category->getId(),
                        $optionsFacetedData[$category->getId()]['count']
                    );
                }
            }
        }

        $itemsData = $this->itemDataBuilder->build();
        if (count($itemsData) == 1
            && !$this->isOptionReducesResults(
                $itemsData[0]['count'],
                $this->getLayer()->getProductCollection()->getSize()
            )
        ) {
            $itemsData = $this->filterRequestDataResolver->getReducedItemsData($this, $itemsData);
        }

        if ($this->getSetting()->getSortOptionsBy() == \Amasty\Shopby\Model\Source\SortOptionsBy::NAME) {
            usort($itemsData, [$this, 'sortOption']);
        }

        return $itemsData;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function sortOption($a, $b)
    {
        return strcmp($a['label'], $b['label']);
    }

    private function getExtendedCategoryData(): array
    {
        try {
            $optionsFacetedData = $this->getFacetedData();
        } catch (StateException $e) {
            return [];
        }

        $startCategory = $this->getStartCategory();
        $startPath = $startCategory->getPath();

        $collection = $this->getExtendedCategoryCollection($startCategory);
        $currentCategoryParents = $this->getLayer()->getCurrentCategory()->getParentIds();
        foreach ($collection as $category) {
            $isAllowed = $this->isAllowedOnEnterprise($category);
            if (!isset($optionsFacetedData[$category->getId()])
                || !$isAllowed
                || (!$this->isRenderAllTree()
                    && !in_array($category->getParentId(), $currentCategoryParents)
                    && $this->getCategoriesTreeDept() != self::MIN_CATEGORY_DEPTH
                    && strpos($category->getPath(), $startPath) !== 0
                )
            ) {
                continue;
            }

            $this->categoryExtendedDataBuilder->addItemData(
                $category->getParentPath(),
                $this->escaper->escapeHtml($category->getName()),
                $category->getId(),
                $optionsFacetedData[$category->getId()]['count']
            );
        }
        $itemsData = [];
        $itemsData['count'] = $this->categoryExtendedDataBuilder->getItemsCount();
        $itemsData['startPath'] = $startPath;
        $itemsData['items'] = $this->categoryExtendedDataBuilder->build();

        if ($this->getSetting()->getSortOptionsBy() == \Amasty\Shopby\Model\Source\SortOptionsBy::NAME) {
            foreach ($itemsData['items'] as $path => &$items) {
                usort($items, [$this, 'sortOption']);
            }
        }

        return $itemsData;
    }

    private function isAllowedOnEnterprise(CategoryModel $category): bool
    {
        $isAllowed = true;
        if ($this->productMetadata->getEdition() !== ProductMetadata::EDITION_NAME) {
            $permissions = $category->getPermissions();
            if (isset($permissions['grant_catalog_category_view'])) {
                $isAllowed = $permissions['grant_catalog_category_view'] !== self::DENY_PERMISSION;
            }
        }

        return $isAllowed;
    }

    /**
     * @param CategoryModel $startCategory
     * @return CategoryCollection|CategoryFlatCollection
     * @throws LocalizedException
     */
    private function getExtendedCategoryCollection(CategoryModel $startCategory)
    {
        $excludedCategoryCollection = $this->getExcludedCategoryCollection();
        if ($excludedCategoryCollection && $excludedCategoryCollection->getItemById($startCategory->getEntityId())) {
            /** @var CategoryCollection $emptyCollection */
            $emptyCollection = $startCategory->getCollection();
            $emptyCollection->getSelect()->where('null');

            return $emptyCollection;
        }

        $minLevel = $startCategory->getLevel();
        $maxLevel = $minLevel + $this->getCategoriesTreeDept();

        /** @var CategoryCollection|CategoryFlatCollection $collection */
        $collection = $startCategory->getCollection();
        $isFlat = $collection instanceof \Magento\Catalog\Model\ResourceModel\Category\Flat\Collection;
        $mainTablePrefix = $isFlat ? 'main_table.' : '';
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter($mainTablePrefix . 'is_active', 1)
            ->addFieldToFilter($mainTablePrefix . 'path', ['like' => $startCategory->getPath() . '%'])
            ->addFieldToFilter($mainTablePrefix . 'level', ['gt' => $minLevel])
            ->setOrder(
                $mainTablePrefix . 'position',
                \Magento\Framework\DB\Select::SQL_ASC
            );

        if ($excludedCategoryCollection) {
            $this->excludeCategories($collection, $mainTablePrefix, $excludedCategoryCollection);
        }
        if (!$this->isRenderAllTree()) {
            $collection->addFieldToFilter($mainTablePrefix . 'level', ['lteq' => $maxLevel]);
        }

        $mainTablePrefix = $isFlat ? 'main_table.' : 'e.';
        $collection->getSelect()->joinLeft(
            ['parent' => $collection->getMainTable()],
            $mainTablePrefix . 'parent_id = parent.entity_id',
            ['parent_path' => 'parent.path']
        );

        return $collection;
    }

    /**
     * @param CategoryCollection|CategoryFlatCollection $collection
     * @param string $mainTablePrefix
     * @param CategoryCollection $excludedCategoryCollection
     */
    private function excludeCategories(
        $collection,
        string $mainTablePrefix,
        CategoryCollection $excludedCategoryCollection
    ): void {
        foreach ($excludedCategoryCollection->getColumnValues('path') as $categoryPath) {
            $collection->addFieldToFilter($mainTablePrefix . 'path', ['nlike' => $categoryPath . '/%'])
                ->addFieldToFilter($mainTablePrefix . 'path', ['neq' => $categoryPath]);
        }
    }

    private function getExcludedCategoryCollection(): ?CategoryCollection
    {
        try {
            $rootPath = $this->categoryRepository->get($this->categoryManager->getRootCategoryId())->getPath();

            return $this->categoryCollectionFactory->create()
                ->addFieldToFilter('path', ['like' => $rootPath . '/%'])
                ->setStore($this->getStoreId())
                ->addAttributeToFilter(self::EXCLUDE_CATEGORY_FROM_FILTER, self::TRUE);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @return array
     */
    protected function getFacetedData()
    {
        $optionsFacetedData = [];

        $productCollection = $this->getLayer()->getProductCollection();
        if ($productCollection instanceof ShopbyFulltextCollection) {
            $result = $this->getSearchResult();
            try {
                $optionsFacetedData = $productCollection->getFacetedData(
                    self::FILTER_FIELD,
                    $result
                );
            } catch (StateException $e) {
                $this->catchBucketException();
            }
        }

        return $optionsFacetedData;
    }

    private function catchBucketException(): void
    {
        $currentValue = $this->filterRequestDataResolver->getCurrentValue($this);
        if (is_array($currentValue)) {
            $categoryId = current($currentValue);
            try {
                $category = $this->categoryRepository->get(
                    $categoryId,
                    $this->categoryManager->getCurrentStoreId()
                );
            } catch (NoSuchEntityException $e) {
                $category = $this->getRootCategory();
            }
        } else {
            $category = $this->getRootCategory();
        }

        $this->messageManager->addErrorMessage(
            __(
                'Make sure that "%1"(id:%2) category for current store is anchored',
                $category->getName(),
                $category->getId()
            )
        );
    }

    /**
     * Retrieve start category for bucket prepare
     * @return CategoryModel
     */
    private function getStartCategory(): CategoryModel
    {
        if ($this->getCategoriesTreeDept() == self::MIN_CATEGORY_DEPTH
            && !$this->getLayer()->getCurrentCategory()->getChildrenCount()
            && !$this->isRenderAllTree()
        ) {
            return $this->getLayer()->getCurrentCategory()->getParentCategory();
        }

        return $this->categoryHelper->getStartCategory();
    }

    /**
     * Retrieve root category for current store
     *
     * @return CategoryModel
     * @throws NoSuchEntityException
     */
    private function getRootCategory(): CategoryModel
    {
        if (!$this->getData('root_category')) {
            $category = $this->categoryRepository->get(
                $this->categoryManager->getRootCategoryId(),
                $this->categoryManager->getCurrentStoreId()
            );
            $this->setData('root_category', $category);
        }

        return $this->getData('root_category');
    }

    private function getSearchResult(): ?SearchResultInterface
    {
        $searchResult = null;
        $isCurrentLevel = $this->getRenderCategoriesLevel() == RenderCategoriesLevel::CURRENT_CATEGORY_LEVEL;
        $isRootLevel = $this->getRenderCategoriesLevel() == RenderCategoriesLevel::ROOT_CATEGORY;
        $excludeCurrentLevel = $isCurrentLevel || $isRootLevel || $this->isRenderAllTree();

        if ($this->hasCurrentValue() || ($excludeCurrentLevel && $this->isMultiselect())
        ) {
            $categoryId = (int)$this->getCategoryIdByLevel($isCurrentLevel);
            $searchResult = $this->search->search($this->buildSearchCriteria($categoryId));
        }

        return $searchResult;
    }

    private function getCategoryIdByLevel(bool $isCurrentLevel): int
    {
        $isCurrentLevelMultiselect = $isCurrentLevel && $this->isMultiselect();
        $isCurrentCategory = !$this->isRenderAllTree()
            && ($isCurrentLevelMultiselect || $this->getCategoriesTreeDept() == self::MIN_CATEGORY_DEPTH);

        $categoryId = $isCurrentCategory
            ? $this->getLayer()->getCurrentCategory()->getId()
            : $this->getRootCategory()->getId();

        return (int) $categoryId;
    }

    public function hasCurrentValue(): bool
    {
        return $this->filterRequestDataResolver->hasCurrentValue($this);
    }

    public function buildSearchCriteria(int $categoryId): SearchCriteria
    {
        $filter[CategoryHelper::ATTRIBUTE_CODE] = $categoryId;

        return $this->getLayer()->getProductCollection()->getMemSearchCriteria($filter);
    }

    public function getRenderCategoriesLevel(): int
    {
        return (int) $this->getSetting()->getRenderCategoriesLevel();
    }

    public function getCategoriesTreeDept(): int
    {
        return (int) $this->getSetting()->getCategoryTreeDepth();
    }

    public function isRenderAllTree(): bool
    {
        return (bool) $this->getSetting()->getRenderAllCategoriesTree();
    }

    public function isMultiselect(): bool
    {
        return $this->filterSettingResolver->isMultiselectAllowed($this);
    }

    public function useLabelsOnly(): bool
    {
        return $this->getImageDisplayMode() == CategoryTreeDisplayMode::SHOW_LABELS_ONLY;
    }

    public function useLabelsAndImages(): bool
    {
        return $this->getImageDisplayMode() == CategoryTreeDisplayMode::SHOW_LABELS_IMAGES;
    }

    public function useImagesOnly(): bool
    {
        return $this->getImageDisplayMode() == CategoryTreeDisplayMode::SHOW_IMAGES_ONLY;
    }

    public function getImageDisplayMode(): int
    {
        return (int) $this->getSetting()->getCategoryTreeDisplayMode();
    }

    public function getSetting(): FilterSettingInterface
    {
        return $this->filterSettingResolver->getFilterSetting($this);
    }

    public function getPosition(): int
    {
        return (int) $this->helper->getCategoryPosition();
    }

    public function getAmpItems(): array
    {
        $data = parent::_getItemsData();
        $items = [];
        foreach ($data as $itemData) {
            $items[] = parent::_createItem($itemData['label'], $itemData['value'], $itemData['count']);
        }
        $this->_items = $items;

        return $items;
    }
}
