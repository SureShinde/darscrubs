<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;

class SitemapBuilder
{
    const XML_PATH_CATEGORY_PRIORITY = 'sitemap/category/priority';
    const XML_PATH_PAGE_CHANGEFREQ = 'sitemap/page/changefreq';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Url
     */
    private $url;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Url $url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
    }

    /**
     * @param array $items
     * @param $storeId
     * @return \Magento\Framework\DataObject[]
     */
    public function prepareItems(array $items, $storeId)
    {
        return array_map(function ($item) use ($storeId) {
            return new \Magento\Framework\DataObject([
                'url' => $item->getUrl(),
                'priority' => $this->getPriority($storeId),
                'changeFrequency' => $this->getChangeFrequency($storeId),
            ]);
        }, $items);
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getPriority($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getChangeFrequency($storeId)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PAGE_CHANGEFREQ,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
