<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav;

use Amasty\GroupedOptions\Model\GroupAttr\Query\GetRelatedOptions;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source as EavSource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

class GetGroupedIndexData
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

    /**
     * @var GetRelatedOptions
     */
    private $getRelatedOptions;

    public function __construct(
        EavSource $eavSource,
        MetadataPool $metadataPool,
        GetRelatedOptions $getRelatedOptions
    ) {
        $this->connection = $eavSource->getConnection();
        $this->indexTable = $eavSource->getMainTable();
        $this->entityMetadata = $metadataPool->getMetadata(ProductInterface::class);
        $this->getRelatedOptions = $getRelatedOptions;
    }

    /**
     * @param array $conditions Conditions for product id field.
     * @return array
     */
    public function execute(array $conditions = []): array
    {
        $select = $this->connection
            ->select()
            ->distinct(true)
            ->from($this->indexTable)
            ->where('value IN(?)', $this->getOptionIdsUsedInGroupOptions());
        foreach ($conditions as $condition) {
            $conditionString = $this->connection->prepareSqlCondition(
                $this->entityMetadata->getIdentifierField(),
                $condition
            );
            $select->where($conditionString);
        }

        $productIndex = $this->connection->fetchAll($select);
        if (!$productIndex) {
            return [];
        }

        $groupedIndexData = [];
        $groupedOptions = $this->getRelatedOptions->execute();
        foreach ($productIndex as $key => $productIndexData) {
            $optionValue = $productIndexData['value'];
            $attributeId = $productIndexData['attribute_id'];
            if (isset($groupedOptions[$attributeId][$optionValue])) {
                foreach ($groupedOptions[$attributeId][$optionValue] as $groupedOptionId) {
                    $groupedIndexRow = $productIndexData;
                    $groupedIndexRow['value'] = $groupedOptionId;
                    $groupedIndexData[] = $groupedIndexRow;
                }
            }

            unset($productIndex[$key]); //reduce memory consumption
        }

        return $groupedIndexData;
    }

    private function getOptionIdsUsedInGroupOptions(): array
    {
        $optionIds = [];

        $relatedOptions = $this->getRelatedOptions->execute();
        foreach ($relatedOptions as $attributeId => $options) {
            array_push($optionIds, ...array_keys($options));
        }

        return $optionIds;
    }
}
