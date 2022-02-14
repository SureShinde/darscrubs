<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Search\FilterMapper;

use Amasty\Shopby\Model\ResourceModel\Search\FilterMapper\CustomExclusionStrategy\OperationInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Request\FilterInterface;

class CustomExclusionStrategyPool
{
    /**
     * @var array
     */
    private $operationPool;

    public function __construct(array $operationPool = [])
    {
        $this->operationPool = $operationPool;
    }

    public function applyFilter(FilterInterface $filter, Select $select): bool
    {
        foreach ($this->operationPool as $key => $operation) {
            if ($operation instanceof OperationInterface && $filter->getField() == $key) {
                $operation->applyFilter($filter, $select);
                return true;
            }
        }

        return false;
    }
}
