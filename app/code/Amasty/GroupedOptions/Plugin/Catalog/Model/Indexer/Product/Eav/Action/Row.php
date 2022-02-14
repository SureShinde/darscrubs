<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Catalog\Model\Indexer\Product\Eav\Action;

use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav\GetGroupedIndexData;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav\InsertIndexData;
use Magento\Catalog\Model\Indexer\Product\Eav\Action\Row as IndexerEavActionRow;

class Row
{
    /**
     * @var GetGroupedIndexData
     */
    private $getGroupedIndexData;

    /**
     * @var InsertIndexData
     */
    private $insertIndexData;

    public function __construct(
        GetGroupedIndexData $getGroupedIndexData,
        InsertIndexData $insertIndexData
    ) {
        $this->getGroupedIndexData = $getGroupedIndexData;
        $this->insertIndexData = $insertIndexData;
    }

    /**
     * @param IndexerEavActionRow $indexer
     * @param int|null $productId
     * @return void
     */
    public function afterExecute(IndexerEavActionRow $indexer, $productId): void
    {
        $groupedIndexData = $this->getGroupedIndexData->execute([
            ['eq' => $productId]
        ]);

        if ($groupedIndexData) {
            $this->insertIndexData->execute($groupedIndexData);
        }
    }
}
