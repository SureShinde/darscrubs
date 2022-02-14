<?php

declare(strict_types=1);

namespace Amasty\ShopbyPage\Model\XmlSitemap\Source;

use Amasty\ShopbyPage\Model\ResourceModel\Page\CollectionFactory;
use Amasty\XmlSitemap\Api\SitemapInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Url;

/**
 * Amasty_XmlSitemap entity provider
 */
class CustomPage
{
    private const ENTITY_CODE = 'amasty_shopbypage';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Url
     */
    private $url;

    public function __construct(
        CollectionFactory $collectionFactory,
        Url $url
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->url = $url;
    }

    public function getData(SitemapInterface $sitemap): \Generator
    {
        /** @var \Amasty\XmlSitemap\Model\Sitemap\SitemapEntityData $sitemapEntityData */
        $sitemapEntityData = $sitemap->getEntityData($this->getEntityCode());
        $storeId = $sitemap->getStoreId();

        foreach ($this->getPages($storeId) as $page) {
            yield [
                [
                    'loc' => $page->getUrl(),
                    'frequency' => $sitemapEntityData->getFrequency(),
                    'priority' => $sitemapEntityData->getPriority()
                ]
            ];
        }
    }

    private function getPages(int $storeId): array
    {
        /** @var \Amasty\ShopbyPage\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('url', ['neq' => ''])
            ->addStoreFilter($storeId);

        foreach ($collection as &$page) {
            if (strpos($page->getUrl(), $this->url->getBaseUrl()) === false) {
                $page->setUrl($this->url->getBaseUrl() . $page->getUrl());
            }
        }

        return $collection->getItems();
    }

    public function getEntityCode(): string
    {
        return self::ENTITY_CODE;
    }

    public function getEntityLabel(): Phrase
    {
        return __('Amasty Custom Pages');
    }
}
