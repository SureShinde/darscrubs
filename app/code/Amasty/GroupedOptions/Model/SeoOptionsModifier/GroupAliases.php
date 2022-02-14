<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\SeoOptionsModifier;

use Amasty\GroupedOptions\Model\GroupAttr\DataProvider;
use Amasty\ShopbySeo\Model\SeoOptionsModifier\UniqueBuilder;

class GroupAliases
{
    /**
     * @var UniqueBuilder|null
     */
    private $uniqueBuilder;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    public function __construct(
        DataProvider $dataProvider,
        array $data = []
    ) {
        $this->uniqueBuilder = $data['uniqueBuilder'] ?? null;
        $this->dataProvider = $dataProvider;
    }

    public function modify(array &$optionsSeoData, int $storeId, array $attributeIds = []): void
    {
        foreach ($attributeIds as $id => $code) {
            $data = $this->getAliasGroup((int) $id);
            if ($data) {
                foreach ($data as $key => $record) {
                    $alias = $this->getUniqueBuilder() ? $this->getUniqueBuilder()->execute($record) : $record;
                    $optionsSeoData[$storeId][$code][$key] = $alias;
                }
            }
        }
    }

    private function getAliasGroup(int $attributeId): array
    {
        $data = [];
        $groups = $this->dataProvider->getGroupsByAttributeId($attributeId);

        foreach ($groups as $group) {
            $url = $group->getUrl() ?: $group->getGroupCode();
            $data[$group->getGroupCode()] = $url;
        }

        return $data;
    }

    private function getUniqueBuilder(): ?UniqueBuilder
    {
        return $this->uniqueBuilder;
    }
}
