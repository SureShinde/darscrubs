<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\Attribute\Backend\Image;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCategoryThumbnail implements DataPatchInterface
{
    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function apply()
    {
        /** @var CategorySetup $eavSetup */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Category::ENTITY);
        $attributeSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);
        if (!$eavSetup->getAttribute('catalog_category', 'thumbnail')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'thumbnail',
                [
                    'type' => 'varchar',
                    'label' => 'Thumbnail',
                    'input' => 'image',
                    'backend' => Image::class,
                    'required' => false,
                    'sort_order' => 5,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                ]
            );

            $idGroup = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'General Information');
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $idGroup,
                'thumbnail',
                45
            );
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
