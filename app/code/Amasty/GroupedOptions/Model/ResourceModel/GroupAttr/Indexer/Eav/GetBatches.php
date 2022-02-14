<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav;

use Generator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source as EavSource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Zend_Db_Expr;

class GetBatches
{
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $indexTable;

    /**
     * @var EntityMetadataInterface
     */
    private $entityMetadata;

    public function __construct(
        EavSource $eavSource,
        MetadataPool $metadataPool
    ) {
        $this->connection = $eavSource->getConnection();
        $this->indexTable = $eavSource->getMainTable();
        $this->entityMetadata = $metadataPool->getMetadata(ProductInterface::class);
    }

    public function execute(int $batchSize): Generator
    {
        $maxLinkFieldValue = $this->connection->fetchOne(
            $this->connection->select()->from(
                ['entity' => $this->indexTable],
                [
                    'max_value' => new Zend_Db_Expr(
                        sprintf('MAX(entity.%s)', $this->entityMetadata->getIdentifierField())
                    )
                ]
            )
        );

        /** @var int $truncatedBatchSize size of the last batch that is smaller than expected batch size */
        $truncatedBatchSize = $maxLinkFieldValue % $batchSize;
        /** @var int $fullBatchCount count of the batches that have expected batch size */
        $fullBatchCount = ($maxLinkFieldValue - $truncatedBatchSize) / $batchSize;

        for ($batchIndex = 0; $batchIndex < $fullBatchCount; $batchIndex ++) {
            yield ['from' => $batchIndex * $batchSize + 1, 'to' => ($batchIndex + 1) * $batchSize];
        }
        // return the last batch if it has smaller size
        if ($truncatedBatchSize > 0) {
            yield ['from' => $fullBatchCount * $batchSize + 1, 'to' => $maxLinkFieldValue];
        }
    }
}
