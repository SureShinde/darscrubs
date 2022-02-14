<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search\DataProvider\Aggregation;

use Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool\OperationInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

class CustomFilterPool
{

    private $operationPool = [];

    public function __construct($operationPool = [])
    {
        $this->operationPool = $operationPool;
    }

    public function getAggregation(string $fieldName, Table $entityIdsTable, array $dimentions = []): ?Select
    {
        foreach ($this->operationPool as $key => $operation) {
            if ($operation instanceof OperationInterface
                && $fieldName == $key
                && $operation->isActive()
            ) {
                return $operation->getAggregation($entityIdsTable, $dimentions);
            }
        }

        return null;
    }
}
