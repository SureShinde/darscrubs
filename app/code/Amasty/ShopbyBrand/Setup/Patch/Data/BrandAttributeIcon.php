<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup\Patch\Data;

use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Api\Data\FilterSettingRepositoryInterface;
use Amasty\ShopbyBase\Helper\Data as BaseHelper;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class BrandAttributeIcon implements DataPatchInterface
{
    /**
     * @var BaseHelper
     */
    private $baseHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var FilterSettingRepositoryInterface
     */
    private $filterSettingRepository;

    public function __construct(
        BaseHelper $baseHelper,
        ScopeConfigInterface $scopeConfig,
        FilterSettingRepositoryInterface $filterSettingRepository
    ) {
        $this->baseHelper = $baseHelper;
        $this->scopeConfig = $scopeConfig;
        $this->filterSettingRepository = $filterSettingRepository;
    }

    public function apply()
    {
        if (!$this->baseHelper->isShopbyInstalled()
            && $this->scopeConfig->isSetFlag('amshopby_brand/general/product_icon')
            && ($attributeCode = $this->scopeConfig->getValue('amshopby_brand/general/attribute_code'))
        ) {
            $filterCode = FilterSetting::ATTR_PREFIX . $attributeCode;
            try {
                $filter = $this->filterSettingRepository->get($filterCode, FilterSettingInterface::FILTER_CODE);
            } catch (NoSuchEntityException $e) {
                return $this;
            }

            $filter->setShowIconsOnProduct(true);
            $this->filterSettingRepository->save($filter);
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
