<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Schema;

use Amasty\Shopby\Api\CmsPageRepositoryInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Add Foreign key for page_id column of amasty_amshopby_cms_page table.
 * Key depends on staging functionality is installed or not.
 */
class AddCmsPageReference implements SchemaPatchInterface
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

        $referenceTable = 'cms_page';
        $referenceColumn = 'page_id';
        if ($connection->isTableExists($this->schemaSetup->getTable('sequence_cms_page'))) {
            $referenceTable = 'sequence_cms_page';
            $referenceColumn = 'sequence_value';
        }

        $connection->addForeignKey(
            $connection->getForeignKeyName(
                CmsPageRepositoryInterface::TABLE,
                'page_id',
                $referenceTable,
                $referenceColumn
            ),
            $this->schemaSetup->getTable(CmsPageRepositoryInterface::TABLE),
            'page_id',
            $this->schemaSetup->getTable($referenceTable),
            $referenceColumn
        );

        $this->schemaSetup->endSetup();

        return $this;
    }

    private function removeOldReference(): void
    {
        $connection = $this->schemaSetup->getConnection();
        $mainTable = $this->schemaSetup->getTable(CmsPageRepositoryInterface::TABLE);
        foreach ($connection->getForeignKeys($mainTable) as $foreignKey) {
            if ($foreignKey['REF_COLUMN_NAME'] === 'page_id') {
                $connection->dropForeignKey($mainTable, $foreignKey['FK_NAME']);
            }
        }
    }

    public static function getDependencies()
    {
        return [AddCmsPageUniqueKey::class];
    }

    public function getAliases()
    {
        return [];
    }
}
