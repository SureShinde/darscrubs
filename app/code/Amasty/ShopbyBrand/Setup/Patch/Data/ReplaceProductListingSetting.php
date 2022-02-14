<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;

class ReplaceProductListingSetting implements DataPatchInterface
{
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $config
    ) {
        $this->configWriter = $configWriter;
        $this->config = $config;
    }

    public function apply()
    {
        $isValueSet = $this->config->isSetFlag(
            'amshopby_brand/product_listing_settings/show_on_listing',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isValueSet) {
            $condition = $this->config->getValue('amshopby_brand/general/show_on_listing', ScopeInterface::SCOPE_STORE);
            if ($condition !== null) {
                $this->configWriter->save('amshopby_brand/product_listing_settings/show_on_listing', $condition);
            }
        }

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
