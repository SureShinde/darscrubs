<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\GroupAttr;

use Amasty\GroupedOptions\Api\Data\GroupAttrInterface;
use Amasty\GroupedOptions\Model\FakeKeyGenerator;
use Magento\Framework\Data\Collection\AbstractDb;

class LoadRelatedOptions
{
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

    public function execute(?int $attributeId, bool $enabledOnly): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addOrder(GroupAttrInterface::POSITION, AbstractDb::SORT_ORDER_ASC);
        if ($attributeId !== null) {
            $collection->addFieldToFilter(GroupAttrInterface::ATTRIBUTE_ID, $attributeId);
        }
        if ($enabledOnly) {
            $collection->addFieldToFilter(GroupAttrInterface::ENABLED, 1);
        }

        $collection->addFieldToSelect([GroupAttrInterface::ATTRIBUTE_ID, GroupAttrInterface::GROUP_CODE]);
        $collection->joinOptions();

        $collection->getSelect()->columns(
            'group_concat(`aagao`.`option_id`) as options'
        )->group(
            GroupAttrInterface::ID
        );

        $loadedData = $collection->getConnection()->fetchAll($collection->getSelect());

        $options = [];
        foreach ($loadedData as $group) {
            if (!$group['options']) {
                continue; //price type
            }
            
            foreach (explode(',', $group['options']) as $attributeOptionId) {
                $options[$group[GroupAttrInterface::ATTRIBUTE_ID]][$attributeOptionId][]
                    = $this->fakeKeyGenerator->generate((int) $group[GroupAttrInterface::ID]);
            }
        }

        return $options;
    }
}
