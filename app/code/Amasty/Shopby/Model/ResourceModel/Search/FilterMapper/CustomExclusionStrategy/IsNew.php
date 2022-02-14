<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search\FilterMapper\CustomExclusionStrategy;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class IsNew implements OperationInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $productIdLink;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    private $aliasExtracter;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        ProductResource $productResource,
        EavConfig $eavConfig,
        TimezoneInterface $timezone,
        AliasExtracter $aliasExtracter
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->productIdLink = $productResource->getLinkField();
        $this->eavConfig = $eavConfig;
        $this->timezone = $timezone;
        $this->aliasExtracter = $aliasExtracter;
    }

    /**
     * @param FilterInterface $filter
     * @param Select $select
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function applyFilter(FilterInterface $filter, Select $select): void
    {
        $mainTableAlias = $this->aliasExtracter->execute($select);
        $mainTable = $this->resourceConnection->getTableName('catalog_product_entity_datetime');
        $storeId = (int) $this->storeManager->getStore()->getId();

        $this->addJoinConditionToSelect(
            'from',
            '',
            $mainTableAlias,
            $select,
            $mainTable,
            $storeId
        );
        $this->addJoinConditionToSelect(
            'from',
            '_default',
            $mainTableAlias,
            $select,
            $mainTable,
            Store::DEFAULT_STORE_ID
        );
        $this->addJoinConditionToSelect(
            'to',
            '',
            $mainTableAlias,
            $select,
            $mainTable,
            $storeId
        );
        $this->addJoinConditionToSelect(
            'to',
            '_default',
            $mainTableAlias,
            $select,
            $mainTable,
            Store::DEFAULT_STORE_ID
        );

        $this->addWhere($select);
    }

    private function addJoinConditionToSelect(
        string $fromTo,
        string $valueOrDefault,
        string $mainTableAlias,
        Select $select,
        string $mainTable,
        int $storeId
    ): void {
        $joinConditions = [
            sprintf(
                "news_%s_date_attribute%s.attribute_id = %s",
                $fromTo,
                $valueOrDefault,
                $this->getAttributeId("news_{$fromTo}_date")
            ),
            sprintf(
                '%s.entity_id = news_%s_date_attribute%s.%s',
                $mainTableAlias,
                $fromTo,
                $valueOrDefault,
                $this->productIdLink
            ),
            sprintf("news_%s_date_attribute%s.store_id = %s", $fromTo, $valueOrDefault, $storeId)

        ];

        $select->joinLeft(
            ["news_{$fromTo}_date_attribute{$valueOrDefault}" => $mainTable],
            implode(' AND ', $joinConditions),
            []
        );
    }

    private function addWhere(Select $select): void
    {
        $fromValueOrDefault = 'IF(news_from_date_attribute.value_id > 0, news_from_date_attribute.value, 
                              news_from_date_attribute_default.value)';
        $toValueOrDefault = 'IF(news_to_date_attribute.value_id > 0, news_to_date_attribute.value, 
                            news_to_date_attribute_default.value)';

        $whereConditions = [
            $fromValueOrDefault . ' IS NOT NULL',
            $toValueOrDefault . ' IS NOT NULL'
        ];
        $select->where(implode(' OR ', $whereConditions));

        $todayStartOfDayDate = $this->timezone->date()
            ->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $todayEndOfDayDate = $this->timezone->date()
            ->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $select->where("({$fromValueOrDefault} <= '{$todayEndOfDayDate}' OR {$fromValueOrDefault} IS NULL)");
        $select->where("({$toValueOrDefault} >= '{$todayStartOfDayDate}' OR {$toValueOrDefault} IS NULL)");
    }

    private function getAttributeId(string $attributeCode): int
    {
        $attribute = $this->eavConfig->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeCode
        );

        return (int) $attribute->getAttributeId();
    }
}
