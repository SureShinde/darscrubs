<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source as EavSource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

class InsertIndexData
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

    public function execute(array $groupedIndexData): void
    {
        $this->connection->beginTransaction();

        // @codingStandardsIgnoreStart
        if (isset($groupedIndexData[0]['source_id'])) {
            $insertSql = 'INSERT IGNORE INTO %s (%s, attribute_id, store_id, `value`, source_id) VALUES  %s';
        } else {
            $insertSql = 'INSERT IGNORE INTO %s (%s, attribute_id, store_id, `value`) VALUES  %s';
        }
        // @codingStandardsIgnoreEnd

        $query = sprintf(
            $insertSql,
            $this->indexTable,
            $this->entityMetadata->getIdentifierField(),
            $this->prepareInsertValues($groupedIndexData)
        );

        $this->connection->query($query);
        $this->connection->commit();
    }

    private function prepareInsertValues(array &$insertionData): string
    {
        $statement = '';

        foreach ($insertionData as $key => $insertion) {
            $statement .= sprintf('(%s),', implode(',', $insertion));
            unset($insertionData[$key]); //reduce memory consumption
        }

        return rtrim($statement, ',');
    }
}
