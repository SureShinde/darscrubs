<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateSubmitFilters implements DataPatchInterface
{
    /**
     * @var ConfigInterface
     */
    private $resourceConfig;

    public function __construct(
        ConfigInterface $resourceConfig
    ) {
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function apply()
    {
        if ($this->isSettingSet()) {
            return $this;
        }

        $connection = $this->resourceConfig->getConnection();

        $select = $connection->select()->from(
            $this->resourceConfig->getTable('core_config_data'),
            ['scope', 'scope_id', 'value']
        )->where('path = ?', 'amshopby/general/submit_filters');

        foreach ($connection->fetchAll($select) as $config) {
            $type = $config['value'] === 'by_button_click' ? 1 : 0;

            $connection->insertOnDuplicate(
                $this->resourceConfig->getTable('core_config_data'),
                [
                    'scope_id' => $config['scope_id'],
                    'scope' => $config['scope'],
                    'value' => $type,
                    'path' => 'amshopby/general/submit_filters_on_desktop'
                ]
            );
            $connection->insertOnDuplicate(
                $this->resourceConfig->getTable('core_config_data'),
                [
                    'scope_id' => $config['scope_id'],
                    'scope' => $config['scope'],
                    'value' => $type,
                    'path' => 'amshopby/general/submit_filters_on_mobile'
                ]
            );
        }

        return $this;
    }

    private function isSettingSet(): bool
    {
        $connection = $this->resourceConfig->getConnection();

        $select = $connection->select()
            ->from(
                $this->resourceConfig->getTable('core_config_data'),
                ['COUNT(*)']
            )
            ->where(
                'path IN (?)',
                ['amshopby/general/submit_filters_on_desktop', 'amshopby/general/submit_filters_on_mobile']
            );

        return (bool)(int) $connection->fetchOne($select);
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
