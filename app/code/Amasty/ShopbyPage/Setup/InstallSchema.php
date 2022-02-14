<?php

namespace Amasty\ShopbyPage\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * TODO Delete after Dec. 2021
 * @deprecated migrated to Declarative Scheme
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 * @SuppressWarnings(PHPMD)
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
    }
}
