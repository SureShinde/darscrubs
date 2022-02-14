<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Setup\Patch\Schema;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Add Foreign key for top_cms_block_id and bottom_cms_block_id columns of amasty_amshopby_option_setting table.
 * Key depends on staging functionality is installed or not.
 */
class AddOptionsCmsBlockReference implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public function apply()
    {
        $this->schemaSetup->startSetup();
        $connection = $this->schemaSetup->getConnection();

        $this->removeOldReference();

        $referenceTable = 'cms_block';
        $referenceColumn = 'block_id';
        if ($connection->isTableExists($this->schemaSetup->getTable('sequence_cms_block'))) {
            $referenceTable = 'sequence_cms_block';
            $referenceColumn = 'sequence_value';
        }

        $connection->addForeignKey(
            $connection->getForeignKeyName(
                OptionSettingRepositoryInterface::TABLE,
                OptionSettingInterface::TOP_CMS_BLOCK_ID,
                $referenceTable,
                $referenceColumn
            ),
            $this->schemaSetup->getTable(OptionSettingRepositoryInterface::TABLE),
            OptionSettingInterface::TOP_CMS_BLOCK_ID,
            $this->schemaSetup->getTable($referenceTable),
            $referenceColumn,
            AdapterInterface::FK_ACTION_SET_NULL
        );
        $connection->addForeignKey(
            $connection->getForeignKeyName(
                OptionSettingRepositoryInterface::TABLE,
                OptionSettingInterface::BOTTOM_CMS_BLOCK_ID,
                $referenceTable,
                $referenceColumn
            ),
            $this->schemaSetup->getTable(OptionSettingRepositoryInterface::TABLE),
            OptionSettingInterface::BOTTOM_CMS_BLOCK_ID,
            $this->schemaSetup->getTable($referenceTable),
            $referenceColumn,
            AdapterInterface::FK_ACTION_SET_NULL
        );

        $this->schemaSetup->endSetup();

        return $this;
    }

    private function removeOldReference(): void
    {
        $connection = $this->schemaSetup->getConnection();
        $mainTable = $this->schemaSetup->getTable(OptionSettingRepositoryInterface::TABLE);
        foreach ($connection->getForeignKeys($mainTable) as $foreignKey) {
            if ($foreignKey['REF_COLUMN_NAME'] === 'block_id') {
                $connection->dropForeignKey($mainTable, $foreignKey['FK_NAME']);
            }
        }
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
