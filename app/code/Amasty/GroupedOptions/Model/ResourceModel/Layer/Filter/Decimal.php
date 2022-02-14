<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\Layer\Filter;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

class Decimal extends \Magento\Catalog\Model\ResourceModel\Layer\Filter\Decimal
{
    public function generateRangeSelect(int $attributeId, int $scopeId, Table $entityIdsTable): Select
    {
        return $this->getConnection()->select()
            ->from(
                ['main_table' => $this->getMainTable()],
                [
                    'value' => new \Zend_Db_Expr("'data'"),
                    'min' => 'min(main_table.value)',
                    'max' => 'max(main_table.value)',
                    'count' => 'count(*)'
                ]
            )
            ->where('main_table.attribute_id = ?', $attributeId)
            ->where('main_table.store_id = ? ', $scopeId)
            ->joinInner(
                ['entities' => $entityIdsTable->getName()],
                'main_table.entity_id  = entities.entity_id',
                []
            );
    }
}
