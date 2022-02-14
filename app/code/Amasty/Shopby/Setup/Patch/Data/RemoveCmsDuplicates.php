<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Delete duplicated rows from amasty_amshopby_cms_page table.
 * Keep the row with the lowest entity_id value
 */
class RemoveCmsDuplicates implements DataPatchInterface
{
    /**
     * @var \Amasty\Shopby\Model\ResourceModel\Cms\Page
     */
    private $pageResourceModel;

    public function __construct(\Amasty\Shopby\Model\ResourceModel\Cms\Page $pageResourceModel)
    {
        $this->pageResourceModel = $pageResourceModel;
    }

    public function apply()
    {
        $table = $this->pageResourceModel->getMainTable();
        $connection = $this->pageResourceModel->getConnection();
        $connection->query(
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql no other way for multi table delete
            "DELETE t1 FROM {$table} t1, {$table} t2 WHERE t1.page_id = t2.page_id AND t1.entity_id > t2.entity_id"
        );

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
