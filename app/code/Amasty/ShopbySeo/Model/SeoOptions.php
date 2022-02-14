<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model;

use Amasty\ShopbyBase\Model\Cache\Type;
use Amasty\ShopbySeo\Model\SeoOptionsModifier\UniqueBuilder;
use Magento\Framework\App\Cache;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManager;

class SeoOptions
{
    const CACHE_KEY = 'amshopby_seo_options_data';

    /**
     * @var Json
     */
    private $json;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var array|null
     */
    private $optionsSeoData = [];

    /**
     * @var StateInterface
     */
    private $cacheState;

    /**
     * @var array
     */
    private $modifiers;

    /**
     * @var UniqueBuilder
     */
    private $uniqueBuilder;

    public function __construct(
        Json $json,
        StoreManager $storeManager,
        Cache $cache,
        StateInterface $cacheState,
        UniqueBuilder $uniqueBuilder,
        array $modifiers = []
    ) {
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->cacheState = $cacheState;
        $this->modifiers = $modifiers;
        $this->uniqueBuilder = $uniqueBuilder;
    }

    public function getData(): array
    {
        $storeId = $this->getCurrentStoreId();
        if (!isset($this->optionsSeoData[$storeId]) && $this->cacheState->isEnabled(Type::TYPE_IDENTIFIER) && false) {
            $cached = $this->cache->load($this->getCacheKey());
            if ($cached !== false) {
                $this->optionsSeoData[$storeId] = $this->json->unserialize($cached);
            }
        }

        if (!isset($this->optionsSeoData[$storeId])) {
            $this->loadData();
        }

        return $this->optionsSeoData[$storeId];
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY . $this->getCurrentStoreId();
    }

    private function getCurrentStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }

    public function loadData(): void
    {
        $storeId = $this->getCurrentStoreId();
        $this->optionsSeoData[$storeId] = [];
        $attributeIds = [];
        foreach ($this->modifiers as $modifier) {
            $modifier->modify($this->optionsSeoData, $storeId, $attributeIds);
        }

        $this->uniqueBuilder->clear();
        $this->saveCache();
    }

    private function saveCache(): void
    {
        if ($this->cacheState->isEnabled(Type::TYPE_IDENTIFIER)) {
            $this->cache->save(
                $this->json->serialize($this->optionsSeoData[$this->getCurrentStoreId()]),
                $this->getCacheKey(),
                [Type::CACHE_TAG]
            );
        }
    }
}
