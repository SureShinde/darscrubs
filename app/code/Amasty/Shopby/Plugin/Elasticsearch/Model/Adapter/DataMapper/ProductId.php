<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter\DataMapper;

use Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter\DataMapperInterface;

class ProductId implements DataMapperInterface
{
    const FIELD_NAME = 'product_id';

    /**
     * @param int $entityId
     * @param array $entityIndexData
     * @param int $storeId
     * @param array $context
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function map($entityId, array $entityIndexData, $storeId, $context = []): array
    {
        return [self::FIELD_NAME => (int)$entityId];
    }

    public function isAllowed(): bool
    {
        return true;
    }

    public function getFieldName(): string
    {
        return self::FIELD_NAME;
    }
}
