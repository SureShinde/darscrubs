<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Setup\Patch\Data;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AnchorRootCategories implements DataPatchInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function apply()
    {
        try {
            /** @var CategoryCollection $rootCategories */
            $rootCategories = $this->categoryCollectionFactory->create();
            $rootCategories
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('level', 1);
            foreach ($rootCategories as $category) {
                $category->setIsAnchor(true);
            }

            $rootCategories->save();
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\Exception $e) {
            // "Invalid attribute name: level" while running unit tests in some cases
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
