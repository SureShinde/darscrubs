<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\AmastyElastic\Model\Indexer\Data\Product;

use Amasty\GroupedOptions\Model\FakeKeyGenerator;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\Collection;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;

class ProductDataMapper
{
    /**
     * @var array|null
     */
    private $groupedOptions;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var FakeKeyGenerator
     */
    private $fakeKeyGenerator;

    public function __construct(CollectionFactory $collectionFactory, FakeKeyGenerator $fakeKeyGenerator)
    {
        $this->collectionFactory = $collectionFactory;
        $this->fakeKeyGenerator = $fakeKeyGenerator;
    }

    /**
     * @param mixed $subject
     * @param \Closure $closure
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return array
     */
    public function aroundGetAttributeOptions(
        $subject,
        \Closure $closure,
        \Magento\Eav\Model\Entity\Attribute $attribute
    ) {
        return $closure($attribute) + $this->getGroupedOptions((int)$attribute->getAttributeId());
    }

    private function getGroupedOptions(int $attributeId): array
    {
        if (!isset($this->groupedOptions[$attributeId])) {
            $this->groupedOptions[$attributeId] = [];
            $collection = $this->getGroupCollection($attributeId)
                ->joinOptions();
            $collection->getSelect()->group('group_code');
            foreach ($collection as $option) {
                $fakeKey = $this->fakeKeyGenerator->generate((int)$option->getGroupId());
                $this->groupedOptions[$attributeId][$fakeKey] = $option->getName();
            }
        }

        return $this->groupedOptions[$attributeId];
    }

    private function getGroupCollection(int $attributeId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('enabled', 1);
        $collection->addFieldToFilter('attribute_id', $attributeId);
        $collection->addOrder('position', AbstractDb::SORT_ORDER_ASC);

        return $collection;
    }
}
