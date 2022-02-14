<?php

namespace Amasty\Shopby\Plugin\CatalogSearch\Model\Indexer\Fulltext\Action;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider as MagentoDataProvider;
use Amasty\Shopby\Model\CatalogSearch\Indexer\Fulltext\DataProvider as AmastyDataProvider;

class DataProvider
{
    /**
     * @var AmastyDataProvider
     */
    private $amastyDataProvider;

    public function __construct(AmastyDataProvider $amastyDataProvider)
    {
        $this->amastyDataProvider = $amastyDataProvider;
    }

    /**
     * @param MagentoDataProvider $subject
     * @param callable $proceed
     * @param string $storeId
     * @param array $staticFields
     * @param array|null $productIds
     * @param int|string $lastProductId
     * @param int|string $batchSize
     * @return array
     */
    public function aroundGetSearchableProducts(
        MagentoDataProvider $subject,
        callable $proceed,
        $storeId,
        array $staticFields,
        $productIds = null,
        $lastProductId = 0,
        $batchSize = 100
    ): array {
        return $this->amastyDataProvider->getSearchableProducts(
            (int)$storeId,
            $staticFields,
            $productIds,
            (int)$lastProductId,
            (int)$batchSize
        );
    }
}
