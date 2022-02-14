<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search\DataProvider\Aggregation\CustomFilterPool;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

interface OperationInterface
{
    const ACTIVE_PATH = '/enabled';

    public function isActive(): bool;

    public function getAggregation(Table $entityIdsTable, array $dimensions = []): Select;
}
