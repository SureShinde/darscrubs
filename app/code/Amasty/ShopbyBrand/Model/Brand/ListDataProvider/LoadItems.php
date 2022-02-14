<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Brand\ListDataProvider;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Model\OptionSetting;
use Amasty\ShopbyBase\Model\OptionSettingFactory;
use Amasty\ShopbyBrand\Helper\Data as DataHelper;
use Amasty\ShopbyBrand\Model\Attribute;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterfaceFactory;
use Amasty\ShopbyBrand\Model\BrandSettingProvider;
use Amasty\ShopbyBrand\Model\ProductCount;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\Cache\Type\Collection as CollectionCache;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Load, cache and hydrate Brand items data for output Blocks.
 */
class LoadItems
{
    /**
     * @var ProductCount
     */
    private $productCount;

    /**
     * @var BrandSettingProvider
     */
    private $brandSettingProvider;

    /**
     * @var OptionSettingFactory
     */
    private $optionSettingFactory;

    /**
     * @var Attribute
     */
    private $brandAttribute;

    /**
     * @var CollectionCache
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var int
     */
    private $cacheLifetime;

    /**
     * @var BrandDataInterfaceFactory
     */
    private $brandDataFactory;

    /**
     * @var DataHelper
     */
    private $helper;

    public function __construct(
        ProductCount $productCount,
        BrandSettingProvider $brandSettingProvider,
        OptionSettingFactory $optionSettingFactory,
        Attribute $brandAttribute,
        BrandDataInterfaceFactory $brandDataFactory,
        CollectionCache $cache,
        SerializerInterface $serializer,
        DataHelper $helper,
        ?int $cacheLifetime = 86400
    ) {
        $this->productCount = $productCount;
        $this->brandSettingProvider = $brandSettingProvider;
        $this->optionSettingFactory = $optionSettingFactory;
        $this->brandAttribute = $brandAttribute;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->cacheLifetime = $cacheLifetime;
        $this->brandDataFactory = $brandDataFactory;
        $this->helper = $helper;
    }

    /**
     * @param int $storeId
     *
     * @return BrandDataInterface[]
     */
    public function getItems(int $storeId): array
    {
        $data = $this->getData($storeId);
        return $this->hydrateItems($data);
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getData(int $storeId): array
    {
        $identifier = $this->getCacheKey($storeId);
        $data = $this->cache->load($identifier);
        if ($data !== false) {
            return $this->serializer->unserialize($data);
        }

        $options = $this->brandAttribute->getOptions();
        if ($options === null) {
            return [];
        }

        $data = [];

        foreach ($options as $option) {
            $optionValue = (int) $option->getValue();
            $setting = $this->brandSettingProvider->getItemByStoreIdAndValue($storeId, $optionValue)
                ?? $this->optionSettingFactory->create();

            $data[] = $this->extractData($option, $setting);
        }

        $this->cache->save(
            $this->serializer->serialize($data),
            $identifier,
            $this->getCacheTags(),
            $this->cacheLifetime
        );

        return $data;
    }

    /**
     * @param Option $option
     * @param OptionSettingInterface $setting
     *
     * @return array
     */
    private function extractData(Option $option, OptionSettingInterface $setting): array
    {
        return [
            BrandDataInterface::IS_SHOW_IN_WIDGET => $setting->getIsShowInWidget(),
            BrandDataInterface::IS_SHOW_IN_SLIDER => $setting->getIsShowInSlider(),
            BrandDataInterface::BRAND_ID => $option->getValue(),
            BrandDataInterface::LABEL => trim($setting->getLabel() ? : $option->getLabel()),
            BrandDataInterface::URL => $this->helper->getBrandUrl($option),
            BrandDataInterface::IMG => $setting->getSliderImageUrl(),
            BrandDataInterface::IMAGE => $setting->getImageUrl(),
            BrandDataInterface::ALT => $setting->getSmallImageAlt() ? : $setting->getLabel(),
            BrandDataInterface::DESCRIPTION => $setting->getDescription(true),
            BrandDataInterface::SHORT_DESCRIPTION => $setting->getShortDescription(),
            BrandDataInterface::COUNT => $this->productCount->get($setting->getValue()),
            BrandDataInterface::POSITION => $setting->getSliderPosition()
        ];
    }

    /**
     * @param array $data
     *
     * @return BrandDataInterface[]
     */
    private function hydrateItems(array $data): array
    {
        $items = [];
        foreach ($data as $itemData) {
            $items[] = $this->brandDataFactory->create(['data' => $itemData]);
        }

        return $items;
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getCacheKey(int $storeId): string
    {
        return 'amasty_shopby_brand_items_collection-store' . $storeId;
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        $productAttribute = $this->brandAttribute->getAttribute();
        if ($productAttribute === null) {
            $tags = [];
        } else {
            $tags = $productAttribute->getIdentities();
        }

        $tags[] = OptionSetting::CACHE_TAG;

        return $tags;
    }
}
