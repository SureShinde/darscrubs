<?php

declare(strict_types=1);

namespace Amasty\ShopbyPage\Setup\Patch\Schema;

use Amasty\ShopbyPage\Api\Data\PageInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Add Foreign key for top_block_id and bottom_block_id columns of amasty_amshopby_page table.
 * Key depends on staging functionality is installed or not.
 */
class AddPageCmsBlockReference implements SchemaPatchInterface
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
                PageInterface::TABLE_NAME,
                PageInterface::TOP_BLOCK_ID,
                $referenceTable,
                $referenceColumn
            ),
            $this->schemaSetup->getTable(PageInterface::TABLE_NAME),
            PageInterface::TOP_BLOCK_ID,
            $this->schemaSetup->getTable($referenceTable),
            $referenceColumn,
            AdapterInterface::FK_ACTION_SET_NULL
        );
        $connection->addForeignKey(
            $connection->getForeignKeyName(
                PageInterface::TABLE_NAME,
                PageInterface::BOTTOM_BLOCK_ID,
                $referenceTable,
                $referenceColumn
            ),
            $this->schemaSetup->getTable(PageInterface::TABLE_NAME),
            PageInterface::BOTTOM_BLOCK_ID,
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
        $mainTable = $this->schemaSetup->getTable(PageInterface::TABLE_NAME);
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
