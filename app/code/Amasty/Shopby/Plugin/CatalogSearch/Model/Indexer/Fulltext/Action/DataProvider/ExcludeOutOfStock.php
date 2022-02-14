<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;

use Amasty\Shopby\Model\ConfigProvider;
use Amasty\Shopby\Model\Inventory\Resolver as InventoryResolver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider as MagentoDataProvider;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ExcludeOutOfStock
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var InventoryResolver
     */
    private $inventoryResolver;

    public function __construct(
        InventoryResolver $inventoryResolver,
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
        $this->inventoryResolver = $inventoryResolver;
    }

    public function beforePrepareProductIndex(
        MagentoDataProvider $subject,
        array $indexData,
        array $productData,
        string $storeId
    ): array {
        $productIds = array_keys($indexData);
        if (isset($productData[ProductInterface::TYPE_ID])
            && Configurable::TYPE_CODE === $productData[ProductInterface::TYPE_ID]
            && $this->configProvider->isExcludeOutOfStock()
            && isset($productData['entity_id'])
            && (($key = array_search($productData['entity_id'], $productIds)) !== false)
        ) {
            unset($productIds[$key]);
            if ($productIds) {
                $inStockItems = $this->inventoryResolver->getInStockProducts($productIds, (int) $storeId);
                $inStockItems[] = $productData['entity_id'];
                $indexData = array_intersect_key($indexData, array_flip($inStockItems));
            }
        }

        return [$indexData, $productData, $storeId];
    }
}
