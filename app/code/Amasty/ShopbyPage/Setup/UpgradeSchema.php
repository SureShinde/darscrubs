<?php

namespace Amasty\ShopbyPage\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * TODO Delete after Dec. 2021
 * @deprecated migrated to Declarative Scheme
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 * @SuppressWarnings(PHPMD)
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
    }
}
