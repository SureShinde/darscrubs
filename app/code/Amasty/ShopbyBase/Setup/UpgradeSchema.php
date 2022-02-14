<?php

namespace Amasty\ShopbyBase\Setup;

use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @deprecated migrated to Declarative Scheme. The remaining algorithms cannot be safely migrated
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Amasty\ShopbyBase\Helper\Data
     */
    private $helper;

    public function __construct(
        \Amasty\ShopbyBase\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $version = $context->getVersion();
        if ($this->helper->isShopbyInstalled() && version_compare($version, '2.4.5', '<')) {
            $version = $this->helper->getShopbyVersion();
        }

        if (version_compare($version, '2.7.4', '<')) {
            $this->modifyOptionSettings($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function modifyOptionSettings(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(OptionSettingRepositoryInterface::TABLE);
        $connection = $setup->getConnection();

        $select = $connection->select()
            ->from($table, ['store_id', 'meta_title', 'title', 'value'])
            ->where('store_id NOT IN (?)', 0);

        $optionsInfoForStores = $connection->fetchAll($select);

        $select = $connection->select()
            ->from($table, ['store_id', 'meta_title', 'title', 'value'])
            ->where('store_id IN (?)', 0);

        $optionsInfoForDefaultStore = $connection->fetchAll($select);

        foreach ($optionsInfoForStores as $option) {
            foreach ($optionsInfoForDefaultStore as $optionForDefault) {
                if ($option['value'] == $optionForDefault['value']) {
                    if ($option['meta_title'] == $optionForDefault['meta_title']) {
                        $this->updateOptionData($setup, $table, $option['value'], $option['store_id'], 'meta_title');
                    }

                    if ($option['title'] == $optionForDefault['title']) {
                        $this->updateOptionData($setup, $table, $option['value'], $option['store_id'], 'title');
                    }
                }
            }
        }
    }

    /**
     * @param $setup
     * @param $table
     * @param $value
     * @param $storeId
     * @param $field
     */
    private function updateOptionData($setup, $table, $value, $storeId, $field)
    {
        $sql = 'UPDATE ' . $table . ' SET `' . $field . '` = "" WHERE `value` = '
            . $value . ' AND `store_id` = ' . $storeId . ';';
        $setup->getConnection()->query($sql);
    }
}
