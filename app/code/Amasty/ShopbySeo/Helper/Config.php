<?php

namespace Amasty\ShopbySeo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const MODULE_PATH = 'amasty_shopby_seo/';

    /**
     * @param $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getModuleConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::MODULE_PATH . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId = null
     * @return bool
     */
    public function isSeoUrlEnabled($storeId = null)
    {
        return (bool)$this->getModuleConfig('url/mode', $storeId);
    }

    public function isGenerateSeoByDefault(?int $storeId = null): bool
    {
        return (bool)$this->getModuleConfig('url/is_generate_seo_default', $storeId);
    }

    /**
     * @return string
     */
    public function getOptionSeparator()
    {
        return $this->getModuleConfig('url/option_separator');
    }
}
