<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search\Aggregation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class DataProvider
{
    const INDEX_TABLE_NAME = 'catalog_product_index_eav_decimal';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getMinMaxSelect(int $attributeId, string $tableName, int $storeId): Select
    {
        $select = $this->resourceConnection->getConnection()->select();
        $table = $this->resourceConnection->getTableName(
            self::INDEX_TABLE_NAME
        );
        $select->from(
            ['main_table' => $table],
            [
                'value' => new \Zend_Db_Expr("'data'"),
                'min' => 'min(main_table.value)',
                'max' => 'max(main_table.value)',
                'count' => 'count(*)'
            ]
        )
            ->where('main_table.attribute_id = ?', $attributeId)
            ->where('main_table.store_id = ? ', $storeId);
        $select->joinInner(
            ['entities' => $tableName],
            'main_table.entity_id  = entities.entity_id',
            []
        );

        return $select;
    }
}
