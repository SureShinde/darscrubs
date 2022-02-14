<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Swatches\Block\LayeredNavigation\RenderLayered;

use Amasty\GroupedOptions\Api\Data\GroupAttrInterface;
use Amasty\GroupedOptions\Model\GroupAttr\DataFactoryProviderInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Swatches\Block\LayeredNavigation\RenderLayered;

class AddGroupOptions
{
    const IMAGE_TYPE = 2;
    const COLOR_TYPE = 1;
    const TEXT_TYPE = 0;

    /**
     * @var DataFactoryProviderInterface
     */
    private $dataFactoryProvider;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var Layer
     */
    private $layer;

    /**
     * @var AbstractFilter
     */
    private $filter;

    /**
     * @var RenderLayered
     */
    private $subject;

    public function __construct(
        DataFactoryProviderInterface $dataFactoryProvider,
        AttributeRepositoryInterface $attributeRepository,
        LayerResolver $layerResolver
    ) {
        $this->dataFactoryProvider = $dataFactoryProvider;
        $this->attributeRepository = $attributeRepository;
        $this->layer = $layerResolver->get();
    }

    public function beforeSetSwatchFilter(RenderLayered $subject, AbstractFilter $filter): void
    {
        $this->subject = $subject;
        $this->filter = $filter;
    }

    public function afterGetSwatchData(RenderLayered $subject, array $result): array
    {
        $result = $this->populateWithGroups($result);

        return $result;
    }

    private function populateWithGroups(array $data): array
    {
        $attributeId = (int) ($data['attribute_id'] ?? 0);
        $dataProvider = $this->dataFactoryProvider->create();
        $attributeGroups = $dataProvider->getGroupsByAttributeId($attributeId);
        if (!$attributeGroups) {
            return $data;
        }
        $attributeOptions = [];
        $attributeSwatches = [];
        foreach ($attributeGroups as $group) {
            if ($currentOption = $this->getFilterOption($this->filter->getItems(), $group)) {
                $attributeOptions[$group->getGroupCode()] = $currentOption;
            } elseif ($this->isShowEmptyResults()) {
                $attributeOptions[$group->getGroupCode()] = $this->getUnusedOption($group);
            }
            $attributeSwatches[$group->getGroupCode()] = $this->getUnusedSwatchGroup($group);
        }
        $data['options'] += $attributeOptions;
        $data['swatches'] += $attributeSwatches;

        return $data;
    }

    private function getUnusedSwatchGroup(GroupAttrInterface $groupAttr): array
    {
        return [
            'option_id' => $groupAttr->getId(),
            'type' => $groupAttr->getType(),
            'value' => $groupAttr->getVisual() ?: $groupAttr->getName()
        ];
    }

    private function getUnusedOption(GroupAttrInterface $groupAttr): array
    {
        return [
            'label' => $groupAttr->getLabel(),
            'link' => 'javascript:void();',
            'custom_style' => 'disabled'
        ];
    }

    /**
     * Get option data if visible
     *
     * @param FilterItem[] $filterItems
     * @param GroupAttrInterface $groupAttr
     * @return array
     */
    private function getFilterOption(array $filterItems, GroupAttrInterface $groupAttr): ?array
    {
        $filterItem = $this->getFilterItemById($filterItems, $groupAttr->getGroupCode());
        if ($filterItem && $this->isOptionVisible($filterItem)) {
            $resultOption = $this->getOptionViewData($filterItem, $groupAttr);
        }

        return $resultOption ?? null;
    }

    /**
     * Retrieve filter item by id
     *
     * @param FilterItem[] $filterItems
     * @param string $id
     * @return bool|FilterItem
     */
    private function getFilterItemById(array $filterItems, string $id): ?FilterItem
    {
        foreach ($filterItems as $item) {
            if ($item->getValue() == $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if option should be visible
     *
     * @param FilterItem $filterItem
     *
     * @return bool
     */
    private function isOptionVisible(FilterItem $filterItem): bool
    {
        return !($this->isOptionDisabled($filterItem) && $this->isShowEmptyResults());
    }

    /**
     * Check if option should be disabled
     *
     * @param FilterItem $filterItem
     * @return bool
     */
    private function isOptionDisabled(FilterItem $filterItem): bool
    {
        return !$filterItem->getCount();
    }

    /**
     * Check if attribute values should be visible with no results
     *
     * @return bool
     */
    private function isShowEmptyResults(): bool
    {
        return $this->filter->getAttributeModel()->getIsFilterable()
            != AbstractFilter::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS;
    }

    /**
     * Get view data for option
     *
     * @param FilterItem $filterItem
     * @param GroupAttrInterface $groupAttr
     * @return array
     */
    private function getOptionViewData(FilterItem $filterItem, GroupAttrInterface $groupAttr): array
    {
        $customStyle = '';
        $linkToOption = $this->subject->buildUrl(
            $this->filter->getAttributeModel()->getAttributeCode(),
            $filterItem->getValue()
        );
        if ($this->isOptionDisabled($filterItem)) {
            $customStyle = 'disabled';
            $linkToOption = 'javascript:void();';
        }

        return [
            'label' => $groupAttr->getName(),
            'link' => $linkToOption,
            'custom_style' => $customStyle,
            'amasty_shopby_count' => $filterItem->getCount(),
            'amasty_shopby_filter_item' => $filterItem
        ];
    }
}
