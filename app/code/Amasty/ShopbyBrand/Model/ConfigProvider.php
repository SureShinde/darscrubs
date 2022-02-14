<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider extends ConfigProviderAbstract
{
    const BRAND_ATTRIBUTE_CODE = 'general/attribute_code';

    /**
     * @var string
     */
    protected $pathPrefix = 'amshopby_brand/';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $allBrandAttributeCodes;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($scopeConfig);
        $this->storeManager = $storeManager;
    }

    public function getBrandAttributeCode(?int $storeId = null): string
    {
        //should be scopeconfig because of BTS-10415
        return (string) $this->scopeConfig->getValue(
            $this->pathPrefix . self::BRAND_ATTRIBUTE_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAllBrandAttributeCodes(): array
    {
        if ($this->allBrandAttributeCodes === null) {
            $attributes = [];
            foreach ($this->storeManager->getStores() as $store) {
                $code = $this->getBrandAttributeCode((int) $store->getId());
                if ($code) {
                    $attributes[$store->getId()] = $code;
                }
            }

            $this->allBrandAttributeCodes = $attributes;
        }

        return $this->allBrandAttributeCodes;
    }
}
