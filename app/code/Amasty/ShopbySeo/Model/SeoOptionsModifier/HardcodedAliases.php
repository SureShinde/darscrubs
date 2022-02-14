<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\SeoOptionsModifier;

use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\CollectionFactory as OptionSettingCollectionFactory;
use Amasty\ShopbySeo\Helper\Config;
use Amasty\ShopbySeo\Helper\Data;

class HardcodedAliases
{
    /**
     * @var Data
     */
    private $seoHelper;

    /**
     * @var UniqueBuilder
     */
    private $uniqueBuilder;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var OptionSettingCollectionFactory
     */
    private $optionSettingCollectionFactory;

    public function __construct(
        Data $seoHelper,
        UniqueBuilder $uniqueBuilder,
        Config $configHelper,
        OptionSettingCollectionFactory $optionSettingCollectionFactory
    ) {
        $this->seoHelper = $seoHelper;
        $this->uniqueBuilder = $uniqueBuilder;
        $this->configHelper = $configHelper;
        $this->optionSettingCollectionFactory = $optionSettingCollectionFactory;
    }

    public function modify(array &$optionsSeoData, int $storeId, array &$attributeIds = []): void
    {
        $hardcodedAliases = $this->loadHardcodedAliases($storeId);
        foreach ($hardcodedAliases as $row) {
            if (strpos($row['filter_code'], 'attr_') === 0) {
                $attributeCode = substr($row['filter_code'], strlen('attr_'));
            } else {
                continue;
            }

            if (in_array($attributeCode, $attributeIds)) {
                $alias = $this->uniqueBuilder->execute((string)$row['url_alias'], (string)$row['value']);
                $optionsSeoData[$storeId][$attributeCode][$row['value']] = $alias;
            }
        }
    }

    private function loadHardcodedAliases(int $storeId): array
    {
        $aliases = [];
        if ($this->configHelper->isSeoUrlEnabled($storeId)) {
            $aliases = $this->optionSettingCollectionFactory->create()->getHardcodedAliases($storeId);
        }

        return $aliases;
    }
}
