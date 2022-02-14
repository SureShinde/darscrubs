<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search\FilterMapper\CustomExclusionStrategy;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Store\Model\StoreManagerInterface;

class OnSale implements OperationInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    private $aliasExtracter;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        AliasExtracter $aliasExtracter
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->aliasExtracter = $aliasExtracter;
    }

    /**
     * @param FilterInterface $filter
     * @param Select $select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function applyFilter(FilterInterface $filter, Select $select): void
    {
        $tableName = $this->resourceConnection->getTableName('catalog_product_index_price');
        $mainTableAlias = $this->aliasExtracter->execute($select);

        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $conditions = [
            "catalog_rule.product_id = {$mainTableAlias}.entity_id",
            '(catalog_rule.latest_start_date < NOW() OR catalog_rule.latest_start_date IS NULL)',
            '(catalog_rule.earliest_end_date > NOW() OR catalog_rule.earliest_end_date IS NULL)',
            "catalog_rule.website_id = '{$websiteId}'",
            "catalog_rule.customer_group_id = '{$customerGroupId}'"
        ];
        $select->joinLeft(
            ['catalog_rule' => $this->resourceConnection->getTableName('catalogrule_product_price')],
            implode(' AND ', $conditions),
            null
        );

        $select->joinLeft(
            ['relation' => $this->resourceConnection->getTableName('catalog_product_relation')],
            "relation.child_id = {$mainTableAlias}.entity_id",
            ['parent_id' => 'relation.parent_id']
        );

        $priceIndexConditions = [
            "{$mainTableAlias}.entity_id = on_sale_price_index.entity_id",
            "on_sale_price_index.website_id = {$this->storeManager->getWebsite()->getId()}",
            "on_sale_price_index.customer_group_id = {$customerGroupId}"
        ];
        $select->joinInner(['on_sale_price_index' => $tableName], implode(" AND ", $priceIndexConditions), []);
        $select->where('ifnull(catalog_rule.rule_price, on_sale_price_index.final_price) < on_sale_price_index.price');
    }
}
