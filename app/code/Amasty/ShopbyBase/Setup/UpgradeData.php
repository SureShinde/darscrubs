<?php

namespace Amasty\ShopbyBase\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * TODO Delete after Dec. 2021
 * @deprecated migrated to Declarative Scheme
 * @codeCoverageIgnore
 * phpcs:ignoreFile
 */
class UpgradeData implements UpgradeDataInterface
{
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {}
}
