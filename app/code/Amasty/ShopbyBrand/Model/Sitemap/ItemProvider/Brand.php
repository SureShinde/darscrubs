<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Sitemap\ItemProvider;

use Amasty\ShopbyBase\Model\SitemapBuilder;
use Amasty\ShopbyBrand\Helper\Data as Helper;
use Amasty\ShopbyBrand\Model\Attribute;
use Amasty\ShopbyBrand\Model\XmlSitemap;

class Brand
{
    /**
     * @var SitemapBuilder
     */
    private $sitemapBuilder;

    /**
     * @var Attribute
     */
    private $brandAttribute;

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        SitemapBuilder $sitemapBuilder,
        Attribute $brandAttribute,
        Helper $helper
    ) {
        $this->sitemapBuilder = $sitemapBuilder;
        $this->brandAttribute = $brandAttribute;
        $this->helper = $helper;
    }

    /**
     * @param int $storeId
     * @return array|\Magento\Sitemap\Model\SitemapItemInterface[]
     */
    public function getItems($storeId)
    {
        $options = $this->brandAttribute->getOptions();
        foreach ($options as $option) {
            $option->setData('url', $this->helper->getBrandUrl($option, $storeId, false));
        }

        return $this->sitemapBuilder->prepareItems($options, $storeId);
    }
}
