<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCategoryExcludeFromFilter implements DataPatchInterface
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
        if (!$eavSetup->getAttribute('catalog_category', 'am_exclude_from_filter')) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'am_exclude_from_filter',
                [
                    'type' => 'int',
                    'label' => 'Exclude from Category Filter',
                    'input'    => 'boolean',
                    'source'   => Boolean::class,
                    'required' => false,
                    'visible'  => true,
                    'default'  => 0,
                    'sort_order' => 85,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Display Settings',
                ]
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
