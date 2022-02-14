<?php

namespace Amasty\Shopby\Model\ResourceModel\Fulltext;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection\SearchResultApplier;
use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolver;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver as MysqlSearchCriteriaResolver;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolver as MysqlTotalRecordsResolver;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver
    as ElasticSearchCriteriaResolver;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolver as ElasticTotalRecordsResolver;
use Magento\Framework\Api\Search\SearchCriteria;
use Amasty\Shopby\Model\Search\SearchCriteriaBuilderProvider;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Framework\Exception\StateException;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    const MYSQL_ENGINE = 'mysql';

    /**
     * @var SearchCriteriaBuilderProvider
     */
    private $searchCriteriaBuilderProvider;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaResolver
     */
    private $searchCriteriaResolver;

    /**
     * @var array
     */
    private $searchOrders;

    /**
     * @var \Magento\Framework\Api\Search\SearchResultInterface
     */
    private $searchResult;

    /**
     * @var \Magento\Search\Api\SearchInterface
     */
    private $search;

    /**
     * @var TotalRecordsResolver
     */
    private $totalRecordsResolver;

    /**
     * @var SearchResultApplier
     */
    private $searchResultApplier;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string
     */
    private $searchRequestName;

    /**
     * @var SearchCriteriaBuilderProvider
     */
    private $memCriteriaBuilderProvider;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        SearchCriteriaBuilderProvider $searchCriteriaBuilderProvider,
        \Magento\Search\Api\SearchInterface $search,
        TotalRecordsResolver $totalRecordsResolver,
        SearchCriteriaResolver $searchCriteriaResolver,
        SearchResultApplier $searchResultApplier,
        EngineResolverInterface $engineResolver,
        SearchResultFactory $searchResultFactory,
        $connection = null,
        $searchRequestName = 'catalog_view_container'
    ) {
        $this->searchRequestName = $searchRequestName;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection
        );

        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilderProvider = $searchCriteriaBuilderProvider;
        $this->search = $search;
        $this->totalRecordsResolver = $totalRecordsResolver;
        $this->searchCriteriaResolver = $searchCriteriaResolver;
        $this->searchResultApplier = $searchResultApplier;
        $this->engineResolver = $engineResolver;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->queryText = trim($this->queryText . ' ' . $query);
        return $this;
    }

    public function getSearchCriteria(?array $attributeCodesForRemove): SearchCriteria
    {
        $searchCriteriaBuilderProvider = clone $this->searchCriteriaBuilderProvider;
        $this->prepareSearchTermFilter($searchCriteriaBuilderProvider);
        $this->preparePriceAggregation($searchCriteriaBuilderProvider);
        if (is_array($attributeCodesForRemove) && !empty($attributeCodesForRemove)) {
            foreach ($attributeCodesForRemove as $code) {
                $searchCriteriaBuilderProvider->removeFilter($code);
            }
        }

        return $this->getSearchCriteriaResolver($searchCriteriaBuilderProvider)->resolve();
    }

    /**
     * @param array $filter
     * @return SearchCriteria
     */
    public function getMemSearchCriteria(array $filter = []): SearchCriteria
    {
        $searchCriteriaBuilderProvider = clone $this->getMemSearchCriteriaBuilderProvider();

        foreach ($filter as $field => $value) {
            $searchCriteriaBuilderProvider->addFilter($field, $value);
        }

        return $this->getSearchCriteriaResolver($searchCriteriaBuilderProvider)->resolve();
    }

    /**
     * @return SearchCriteriaBuilderProvider
     */
    private function getMemSearchCriteriaBuilderProvider()
    {
        if ($this->memCriteriaBuilderProvider === null) {
            $this->memCriteriaBuilderProvider = clone $this->searchCriteriaBuilderProvider;
            $this->memCriteriaBuilderProvider->addFilter('scope', $this->getStoreId());
            if ($this->queryText) {
                $this->prepareSearchTermFilter($this->memCriteriaBuilderProvider);
            }
        }

        return $this->memCriteriaBuilderProvider;
    }

    /**
     * @throws LocalizedException
     */
    protected function _renderFiltersBefore()
    {
        if ($this->isLoaded()) {
            return;
        }

        if ($this->searchRequestName !== 'quick_search_container' || strlen(trim($this->queryText))) {
            $this->prepareSearchTermFilter($this->searchCriteriaBuilderProvider);
            $this->preparePriceAggregation($this->searchCriteriaBuilderProvider);

            $searchCriteria = $this->getSearchCriteriaResolver()->resolve();
            try {
                $this->searchResult =  $this->search->search($searchCriteria);
                $this->_totalRecords = $this->getTotalRecordsResolver($this->searchResult)->resolve();
            } catch (EmptyRequestDataException $e) {
                $this->searchResult = $this->createEmptyResult();
            } catch (NonExistingRequestNameException $e) {
                $this->_logger->error($e->getMessage());
                throw new LocalizedException(__('An error occurred. For details, see the error log.'));
            }
        } else {
            $this->searchResult = $this->createEmptyResult();
        }

        $this->getSearchResultApplier($this->searchResult)->apply();
        parent::_renderFiltersBefore();
    }

    /**
     * @return SearchResultInterface
     */
    private function createEmptyResult()
    {
        return $this->searchResultFactory->create()->setItems([]);
    }

    /**
     * @param SearchResultInterface $searchResult
     * @return MysqlTotalRecordsResolver|ElasticTotalRecordsResolver
     */
    private function getTotalRecordsResolver(SearchResultInterface $searchResult)
    {
        return $this->totalRecordsResolver->getResolver(['searchResult' => $searchResult]);
    }

    private function prepareSearchTermFilter(SearchCriteriaBuilderProvider $searchCriteriaBuilderProvider): void
    {
        if ($this->queryText) {
            $searchCriteriaBuilderProvider->addFilter('search_term', $this->queryText);
        }
    }

    private function preparePriceAggregation(SearchCriteriaBuilderProvider $searchCriteriaBuilderProvider): void
    {
        $priceRangeCalculation = $this->_scopeConfig->getValue(
            \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory::XML_PATH_RANGE_CALCULATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($priceRangeCalculation) {
            $searchCriteriaBuilderProvider->addFilter('price_dynamic_algorithm', $priceRangeCalculation);
        }
    }

    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function setOrder($attribute, $dir = Select::SQL_DESC)
    {
        $field = (string)$this->_getMappedField($attribute);
        $direction = strtoupper($dir) == self::SORT_ORDER_ASC ? self::SORT_ORDER_ASC : self::SORT_ORDER_DESC;
        $this->searchOrders[$field] = $direction;
        if ($this->isUseDefaultFilterStrategy()) {
            parent::setOrder($attribute, $dir);
        }

        return $this;
    }

    private function isUseDefaultFilterStrategy(): bool
    {
        return $this->engineResolver->getCurrentSearchEngine() == self::MYSQL_ENGINE;
    }

    /**
     * Stub method for compatibility with other search engines
     *
     * @return $this
     */
    public function setGeneralDefaultQuery()
    {
        return $this;
    }

    /**
     * @param string $attribute
     * @param string $dir
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        if ($this->isUseDefaultFilterStrategy()) {
            return parent::addAttributeToSort($attribute, $dir);
        }

        $this->setOrder($attribute, $dir);
        if ($attribute == 'position') {
            $this->setOrder('product_id', $dir);
        }

        return $this;
    }

    /**
     * @param $field
     * @param null $searchResult
     * @return array
     * @throws StateException
     */
    public function getFacetedData($field, $searchResult = null)
    {
        if (!$searchResult) {
            $this->_renderFilters();
        }

        $searchResult = $searchResult ?: $this->searchResult;

        $result = [];
        $aggregations = $searchResult->getAggregations();
        // This behavior is for case with empty object when we got EmptyRequestDataException
        if (null !== $aggregations) {
            $bucket = $aggregations->getBucket($field . RequestGenerator::BUCKET_SUFFIX);
            if ($bucket) {
                foreach ($bucket->getValues() as $value) {
                    $metrics = $value->getMetrics();
                    $result[$metrics['value']] = $metrics;
                }
            } else {
                throw new StateException(__("The bucket doesn't exist."));
            }
        }
        return $result;
    }

    /**
     * @param array $visibility
     * @return $this|Collection
     */
    public function setVisibility($visibility)
    {
        $this->addFieldToFilter('visibility', $visibility);

        if ($this->isUseDefaultFilterStrategy()) {
            parent::setVisibility($visibility);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _renderFilters()
    {
        $this->_filters = [];
        return parent::_renderFilters();
    }

    /**
     * Specify category filter for product collection
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return $this
     */
    public function addCategoryFilter(\Magento\Catalog\Model\Category $category)
    {
        if ($this->isUseDefaultFilterStrategy()) {
            $this->_productLimitationFilters['category_id'] = $category->getId();
        } else {
            $this->setFlag('has_category_filter', true);
            $this->_productLimitationPrice();
        }

        $this->addFieldToFilter('category_ids', $category->getId());

        return $this;
    }

    /**
     * @param false $printQuery
     * @param false $logQuery
     * @return $this|Collection
     * @throws LocalizedException
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        $this->getEntity();

        $currentSearchEngine = $this->engineResolver->getCurrentSearchEngine();
        if ($this->_pageSize && $currentSearchEngine === self::MYSQL_ENGINE) {
            $this->getSelect()->limitPage($this->getCurPage(), $this->_pageSize);
        }

        $this->printLogQuery($printQuery, $logQuery);

        try {
            $query = $this->getSelect();
            $rows = $this->_fetchAll($query);
        } catch (\Exception $e) {
            $this->printLogQuery(false, true, $query);
            throw $e;
        }

        $position = 0;
        foreach ($rows as $value) {
            if ($this->getFlag('has_category_filter')) {
                $value['cat_index_position'] = $position++;
            }
            $object = $this->getNewEmptyItem()->setData($value);
            $this->addItem($object);
            if (isset($this->_itemsById[$object->getId()])) {
                $this->_itemsById[$object->getId()][] = $object;
            } else {
                $this->_itemsById[$object->getId()] = [$object];
            }
        }
        if ($this->getFlag('has_category_filter')) {
            $this->setFlag('has_category_filter', false);
        }

        return $this;
    }

    /**
     * @param mixed $field
     * @param null $condition
     * @return $this|Collection|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($this->searchResult !== null) {
            throw new \RuntimeException('Illegal state');
        }

        if (!is_array($condition) || !in_array(key($condition), ['from', 'to'], true)) {
            $this->searchCriteriaBuilderProvider->addFilter($field, $condition);
        } else {
            if (isset($condition['from'])) {
                $this->searchCriteriaBuilderProvider->addFilter("{$field}.from", $condition['from']);
            }
            if (isset($condition['to'])) {
                $this->searchCriteriaBuilderProvider->addFilter("{$field}.to", $condition['to']);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _beforeLoad()
    {
        $this->setOrder('entity_id');

        return parent::_beforeLoad();
    }

    private function getSearchResultApplier(SearchResultInterface $searchResult): SearchResultApplierInterface
    {
        return $this->searchResultApplier->getApplier(
            [
                'collection' => $this,
                'searchResult' => $searchResult,
                'orders' => $this->_orders,
                'size' => $this->getPageSize(),
                'currentPage' => (int)$this->_curPage,
            ]
        );
    }

    /**
     * @param SearchCriteriaBuilderProvider|null $searchCriteriaBuilder
     * @return MysqlSearchCriteriaResolver|ElasticSearchCriteriaResolver
     */
    private function getSearchCriteriaResolver(SearchCriteriaBuilderProvider $searchCriteriaBuilderProvider = null)
    {
        $builder = $searchCriteriaBuilderProvider
            ? $searchCriteriaBuilderProvider->create()
            : $this->searchCriteriaBuilderProvider->create();

        return $this->searchCriteriaResolver->getResolver(
            [
                'builder' => $builder,
                'collection' => $this,
                'searchRequestName' => $this->searchRequestName,
                'currentPage' => (int)$this->_curPage,
                'size' => $this->getPageSize(),
                'orders' => $this->searchOrders,
            ]
        );
    }
}
