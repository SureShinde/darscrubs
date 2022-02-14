<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\XmlSitemap\Source;

use Amasty\ShopbyBrand\Helper\Data as Helper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;

class Brand
{
    const ENTITY_CODE = 'amasty_shopbybrand';

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Config
     */
    private $eavConfig;

    public function __construct(
        Helper $helper,
        Config $eavConfig
    ) {
        $this->helper = $helper;
        $this->eavConfig = $eavConfig;
    }

    public function getData($sitemap): \Generator
    {
        /** @var \Amasty\XmlSitemap\Model\Sitemap\SitemapEntityData $sitemapEntityData */
        $sitemapEntityData = $sitemap->getEntityData($this->getEntityCode());
        $storeId = $sitemap->getStoreId();

        foreach ($this->getBrands() as $brand) {
            if ($brand['value']) {
                yield [
                    [
                        'loc' => $this->helper->getBrandUrl($brand, $storeId),
                        'frequency' => $sitemapEntityData->getFrequency(),
                        'priority' => $sitemapEntityData->getPriority()
                    ]
                ];
            }
        }
    }

    private function getBrands(): array
    {
        $options = [];
        $attributeCode = $this->helper->getBrandAttributeCode();

        if ($attributeCode) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
            $options = $attribute->getOptions();
        }

        return $options;
    }

    public function getEntityCode(): string
    {
        return self::ENTITY_CODE;
    }

    public function getEntityLabel(): string
    {
        return __('Amasty Brands')->render();
    }
}
