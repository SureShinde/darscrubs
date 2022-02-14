<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\GroupAttr;

use Amasty\GroupedOptions\Api\Data\GroupAttrInterface;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Collection as GroupAttrCollection;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\CollectionFactory as GroupAttrCollectionFactory;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrOption\Collection as GroupAttrOptionCollection;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrOption\CollectionFactory as GroupAttrOptionCollectionFactory;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrValue\Collection as GroupAttrValueCollection;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrValue\CollectionFactory as GroupAttrValueCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class DataProvider
{
    const ENABLED = 1;

    /**
     * @var GroupAttrCollection
     */
    private $groupAttributeCollection;

    /**
     * @var GroupAttrOptionCollection
     */
    private $groupAttributeOptionCollection;

    /**
     * @var GroupAttrValueCollection
     */
    private $groupAttributeValueCollection;

    /**
     * @var GroupAttrInterface[][]
     */
    private $groupByAttributeId = [];

    /**
     * @var GroupAttrInterface[]
     */
    private $groupByCode = [];

    /**
     * @var StoreLabelResolver
     */
    private $storeLabelResolver;

    public function __construct(
        GroupAttrCollectionFactory $groupAttributeCollectionFactory,
        GroupAttrOptionCollectionFactory $groupAttributeOptionCollectionFactory,
        GroupAttrValueCollectionFactory $groupAttributeValueCollectionFactory,
        StoreLabelResolver $storeLabelResolver
    ) {
        $this->groupAttributeCollection = $groupAttributeCollectionFactory->create();
        $this->groupAttributeOptionCollection = $groupAttributeOptionCollectionFactory->create();
        $this->groupAttributeValueCollection = $groupAttributeValueCollectionFactory->create();
        $this->storeLabelResolver = $storeLabelResolver;
        $this->initGroups();
    }

    /**
     * @return $this
     */
    private function initGroups()
    {
        $groupCollection = $this->groupAttributeCollection->addFieldToFilter('enabled', self::ENABLED)
            ->addOrder('position', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
        foreach ($groupCollection as $item) {
            $item->setName($this->storeLabelResolver->chooseStoreLabel($item->getName()));
            $this->groupByAttributeId[$item->getAttributeId()][] = $item;
            $this->groupByCode[$item->getGroupCode()] = $item;
        }

        foreach ($this->groupAttributeOptionCollection->getItems() as $option) {
            $item = $groupCollection->getItemById($option->getGroupId());
            if ($item !== null) {
                $item->addOption($option);
            }
        }

        foreach ($this->groupAttributeValueCollection->getItems() as $value) {
            $item = $groupCollection->getItemById($value->getGroupId());
            if ($item !== null) {
                $item->addValue($value);
            }
        }

        return $this;
    }

    /**
     * @param int $attributeId
     * @return GroupAttrInterface[]
     */
    public function getGroupsByAttributeId(int $attributeId): array
    {
        return isset($this->groupByAttributeId[$attributeId])
            ? $this->groupByAttributeId[$attributeId] : [];
    }

    public function getByCode(string $code): GroupAttrInterface
    {
        if (!isset($this->groupByCode[$code])) {
            throw new NoSuchEntityException(__('Requested group doesn\'t exist'));
        }

        return $this->groupByCode[$code];
    }

    public function getGroupByAttributeId(int $attributeId, string $groupCode): ?GroupAttrInterface
    {
        $groups = $this->getGroupsByAttributeId($attributeId);
        foreach ($groups as $group) {
            if ($group->getGroupCode() == $groupCode) {
                return $group;
            }
        }

        return null;
    }

    public function getGroupAttributeRanges(int $attributeId): array
    {
        $groupRanges = [];
        $groups = $this->getGroupsByAttributeId($attributeId);
        foreach ($groups as $group) {
            if ($group->hasValues()) {
                $groupRanges[$group->getGroupCode()] = $this->getMinMaxValues($group->getValues());
            }
        }

        return $groupRanges;
    }

    /**
     * @return GroupAttrInterface[]
     */
    public function getAllGroups()
    {
        /**
         * @var GroupAttrInterface[] $items
         */
        $items = $this->groupAttributeCollection->getItems();
        return $items;
    }

    private function getMinMaxValues(array $groupValues): array
    {
        $min = $groupValues[0]->getValue();
        $max = $groupValues[1]->getValue();
        if ($max < $min) {
            return ['min' => $max, 'max' => $min];
        }

        return ['min' => $min, 'max' => $max];
    }
}
