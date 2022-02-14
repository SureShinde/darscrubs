<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * TODO Delete after Dec. 2021
 * @deprecated
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
    }
}
