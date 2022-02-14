<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Catalog\Product\Indexer\Price;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Stdlib\DateTime;

class DefaultPrice
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        ResourceConnection $resourceConnection,
        DateTime $dateTime
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
    }

    /**
     * Method adds modifications to catalog_price_index, related to special prices of simple products
     *
     * @param string $tableName
     * @param array $entityIds
     * @throws \Exception
     */
    public function addSpecialPrice(string $tableName, array $entityIds = []): void
    {
        $columns = [
            'entity_id' => 'main_table.entity_id',
            'customer_group_id' => 'main_table.customer_group_id',
            'website_id' => 'main_table.website_id',
            'tax_class_id' => 'main_table.tax_class_id',
            'price' => 'main_table.price',
            'final_price' => new \Zend_Db_Expr('LEAST(main_table.final_price, rule_index.rule_price)'),
            'min_price' => new \Zend_Db_Expr('LEAST(main_table.min_price, rule_index.rule_price)'),
            'max_price' => new \Zend_Db_Expr('LEAST(main_table.max_price, rule_index.rule_price)'),
            'tier_price' => 'main_table.tier_price',
        ];

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $tableName],
            $columns
        );

        $conditions = [
            'rule_index.product_id = main_table.entity_id',
            'rule_index.website_id = main_table.website_id',
            'rule_index.customer_group_id = main_table.customer_group_id'

        ];
        $select->joinInner(
            ['rule_index' => $this->resourceConnection->getTableName('catalogrule_product_price')],
            implode(' AND ', $conditions),
            []
        );

        $now = new \DateTime();
        $select->where('rule_index.rule_date = ?', $this->dateTime->formatDate($now, false));

        if ($entityIds) {
            $select->where('main_table.entity_id IN (?)', $entityIds);
        }

        $connection->insertFromSelect(
            $select,
            $tableName,
            ['final_price', 'min_price', 'max_price'],
            Mysql::INSERT_ON_DUPLICATE
        );
    }
}
