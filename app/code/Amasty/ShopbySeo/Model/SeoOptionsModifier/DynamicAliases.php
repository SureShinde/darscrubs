<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\SeoOptionsModifier;

use Amasty\ShopbySeo\Helper\Data;
use Amasty\ShopbySeo\Model\ResourceModel\Eav\Model\Entity\Attribute\Option\CollectionFactory;

class DynamicAliases
{
    /**
     * @var Data
     */
    private $seoHelper;

    /**
     * @var CollectionFactory
     */
    private $optionCollectionFactory;

    /**
     * @var UniqueBuilder
     */
    private $uniqueBuilder;

    public function __construct(
        Data $seoHelper,
        CollectionFactory $optionCollectionFactory,
        UniqueBuilder $uniqueBuilder
    ) {
        $this->seoHelper = $seoHelper;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->uniqueBuilder = $uniqueBuilder;
    }

    public function modify(array &$optionsSeoData, int $storeId, array &$attributeIds = []): void
    {
        $dynamicAliases = $this->loadDynamicAliases((int)$storeId);
        foreach ($dynamicAliases as $row) {
            $attributeCode = (string)$row->getAttributeCode();
            if (!array_key_exists($row->getAttributeId(), $attributeIds)) {
                $attributeIds[$row->getAttributeId()] = $attributeCode;
            }
            
            $optionId = $row->getOptionId();
            $alias = $this->uniqueBuilder->execute((string)$row->getValue(), (string)$optionId);
            $optionsSeoData[$storeId][$attributeCode][$optionId] = $alias;
        }
    }

    private function loadDynamicAliases(int $storeId = null): array
    {
        $seoAttributeCodes = $this->seoHelper->getSeoSignificantAttributeCodes();

        $collection = $this->optionCollectionFactory->create()
            ->addAttributeFilter($seoAttributeCodes)
            ->setStoreFilter($storeId);

        return $collection->getItems();
    }
}
