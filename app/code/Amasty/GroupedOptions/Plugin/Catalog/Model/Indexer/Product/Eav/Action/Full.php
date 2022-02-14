<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Catalog\Model\Indexer\Product\Eav\Action;

use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav\GetBatches;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav\GetGroupedIndexData;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Indexer\Eav\InsertIndexData;
use Magento\Catalog\Model\Indexer\Product\Eav\Action\Full as IndexerEavActionFull;

class Full
{
    const BATCH_SIZE = 3000;

    /**
     * @var GetGroupedIndexData
     */
    private $getGroupedIndexData;

    /**
     * @var GetBatches
     */
    private $getBatches;

    /**
     * @var InsertIndexData
     */
    private $insertIndexData;

    public function __construct(
        GetBatches $getBatches,
        GetGroupedIndexData $getGroupedIndexData,
        InsertIndexData $insertIndexData
    ) {
        $this->getGroupedIndexData = $getGroupedIndexData;
        $this->getBatches = $getBatches;
        $this->insertIndexData = $insertIndexData;
    }

    public function afterExecute(IndexerEavActionFull $indexer = null): void
    {
        foreach ($this->getBatches->execute(static::BATCH_SIZE) as $batch) {
            $groupedIndexData = $this->getGroupedIndexData->execute([
                ['gteq' => $batch['from']],
                ['lteq' => $batch['to']]
            ]);

            if ($groupedIndexData) {
                $this->insertIndexData->execute($groupedIndexData);
            }
        }
    }
}
