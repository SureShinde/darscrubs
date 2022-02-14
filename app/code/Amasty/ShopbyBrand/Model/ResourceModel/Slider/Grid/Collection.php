<?php

namespace Amasty\ShopbyBrand\Model\ResourceModel\Slider\Grid;

use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\Collection as BrandCollection;
use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Store\Model\Store;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option;

class Collection extends BrandCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $_aggregations;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ConfigProvider $configProvider,
        Option\CollectionFactory $optionCollectionFactory,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = Document::class,
        $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $optionCollectionFactory,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->configProvider = $configProvider;
        $this->setMainTable($mainTable);
        $this->_prepareCollection();
    }

    /**
     * add current attribute and default store_id filters
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->addAttributeFilter();
        $this->addFieldToFilter('main_table.' . OptionSettingInterface::STORE_ID, Store::DEFAULT_STORE_ID);
        $this->getSelect()
            ->joinInner(
                ['amshopbybrand_option' => $this->getTable('eav_attribute_option')],
                'main_table.value = amshopbybrand_option.option_id',
                []
            )
            ->join(
                ['option' => $this->getTable('eav_attribute_option_value')],
                'option.option_id = main_table.value'
            )
            ->columns(
                'IF(main_table.title != \'\', main_table.title, option.value) as title'
            )
            ->columns(
                'IF(main_table.meta_title != \'\', main_table.meta_title, option.value) as meta_title'
            )
            ->group('option_setting_id');

        return $this;
    }

    protected function addAttributeFilter()
    {
        $filterCodes = [];
        foreach ($this->configProvider->getAllBrandAttributeCodes() as $attrCode) {
            $filterCodes[] = FilterSetting::ATTR_PREFIX . $attrCode;
        }
        
        $this->addFieldToFilter(OptionSettingInterface::FILTER_CODE, ['in' => $filterCodes]);
    }

    /**
     * add second order by title
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $titleField = \Amasty\ShopbyBase\Api\Data\OptionSettingInterface::TITLE;
        if ($field != $titleField) {
            parent::setOrder($field, $direction);
            $field = $titleField;
            $direction = 'ASC';
        }

        return parent::setOrder($field, $direction);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->_aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->_aggregations = $aggregations;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
