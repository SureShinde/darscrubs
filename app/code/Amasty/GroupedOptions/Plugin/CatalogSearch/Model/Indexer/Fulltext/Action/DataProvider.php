<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\CatalogSearch\Model\Indexer\Fulltext\Action;

use Amasty\GroupedOptions\Model\GroupAttr\Query\GetRelatedOptions;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider as MagentoDataProvider;
use Magento\Framework\App\ResourceConnection;

class DataProvider
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var GetRelatedOptions
     */
    private $getRelatedOptions;

    public function __construct(
        GetRelatedOptions $getRelatedOptions,
        ResourceConnection $resourceConnection
    ) {
        $this->resource = $resourceConnection;
        $this->getRelatedOptions = $getRelatedOptions;
    }

    /**
     * @param MagentoDataProvider $subject
     * @param array $indexData
     * @return array
     */
    public function afterGetProductAttributes(MagentoDataProvider $subject, array $indexData)
    {
        $indexData = $this->addGroupedToIndexData($indexData);

        return $indexData;
    }

    /**
     * @param array $indexData
     * @return array
     */
    private function addGroupedToIndexData(array $indexData)
    {
        $groupedOptions = $this->getRelatedOptions->execute();
        foreach ($groupedOptions as $attributeId => $optionData) {
            $allAttributeOptionsContainedInGroups = array_keys($optionData);
            foreach ($indexData as &$product) {
                if (isset($product[$attributeId])) {
                    $productOptions = explode(',', $product[$attributeId]);
                    $intersectedOptionIds = array_intersect($allAttributeOptionsContainedInGroups, $productOptions);
                    if (!$intersectedOptionIds) {
                        continue;
                    }

                    $intersectedGroupedData = array_intersect_key($optionData, array_flip($intersectedOptionIds));
                    if (count($intersectedGroupedData)) {
                        // @codingStandardsIgnoreLine
                        $gropedValues = array_unique(array_merge(...$intersectedGroupedData));
                    } else {
                        $gropedValues = [];
                    }

                    $notGroupedOptions = array_diff($productOptions, $allAttributeOptionsContainedInGroups);
                    //@codingStandardsIgnoreLine
                    $allValues = array_merge($gropedValues, $notGroupedOptions);
                    $product[$attributeId] = implode(',', $allValues);
                }
            }
        }

        return $indexData;
    }
}
