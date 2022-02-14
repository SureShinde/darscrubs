<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup\Patch\Data;

use Amasty\ShopbyBrand\Block\Widget\BrandListAbstract;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DropStoreSpecificBrandAttributeSettings implements DataPatchInterface
{
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    public function __construct(
        ConfigWriter $configWriter,
        StoreManagerInterface $storeManager,
        TypeListInterface $cacheTypeList
    ) {
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function apply()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->configWriter->delete(
                BrandListAbstract::PATH_BRAND_ATTRIBUTE_CODE,
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );
        }

        $this->cacheTypeList->invalidate(ConfigCache::TYPE_IDENTIFIER);

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
