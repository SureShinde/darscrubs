<?php
/**
 */
namespace Onesaas\Connect\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
 
        // Get osconnectkeys table
        $tableName = $setup->getTable('osconnectkeys');
        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            // Declare data

			$sql = "INSERT INTO {$tableName} VALUES (null,CONCAT(MD5(NOW()), MD5(CURTIME())), NOW())";
			$setup->getConnection()->query($sql);
        }
 
        $setup->endSetup();
    }
}
