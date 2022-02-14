<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Inventory;

use Amasty\Shopby\Model\ResourceModel\GetInStockProductIds;
use Amasty\Shopby\Model\ResourceModel\GetMsiInStockProductIds;
use Magento\Framework\Module\Manager;

class Resolver
{
    const WEBSITE_CONDITION_REGEXP = '@(`?website_id`?=\s*)\d+@';

    const DEFAULT_WEBSITE_ID = 0;

    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var GetInStockProductIds
     */
    private $getInStockProductIds;

    /**
     * @var GetMsiInStockProductIds
     */
    private $getMsiInStockProductIds;

    public function __construct(
        Manager $moduleManager,
        GetInStockProductIds $getInStockProductIds,
        GetMsiInStockProductIds $getMsiInStockProductIds
    ) {
        $this->moduleManager = $moduleManager;
        $this->getInStockProductIds = $getInStockProductIds;
        $this->getMsiInStockProductIds = $getMsiInStockProductIds;
    }

    public function getInStockProducts(array $productIds, int $storeId): array
    {
        return $this->isMsiEnabled()
            ? $this->getMsiInStockProductIds->execute($productIds, $storeId)
            : $this->getInStockProductIds->execute($productIds, $storeId);
    }

    /**
     * @return bool
     */
    public function isMsiEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

    /**
     * @param string $websiteCondition
     * @return string
     */
    public function replaceWebsiteWithDefault(string $websiteCondition): string
    {
        return preg_replace(
            self::WEBSITE_CONDITION_REGEXP,
            '$1 ' . self::DEFAULT_WEBSITE_ID,
            $websiteCondition
        );
    }
}
