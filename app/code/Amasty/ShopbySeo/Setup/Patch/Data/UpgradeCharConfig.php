<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Setup\Patch\Data;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpgradeCharConfig implements DataPatchInterface
{
    /**
     * @var ConfigInterface
     */
    private $resourceConfig;

    public function __construct(ConfigInterface $resourceConfig)
    {
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @return UpgradeCharConfig|void
     */
    public function apply()
    {
        $connection = $this->resourceConfig->getConnection();
        $select = $connection->select()->from(
            $this->resourceConfig->getTable('core_config_data'),
            ['scope', 'scope_id', 'value']
        )->where('path = \'amasty_shopby_seo/url/special_char\'');

        foreach ($connection->fetchAll($select) as $config) {
            if ($config['value'] !== '--') {
                continue;
            }

            $connection->insertOnDuplicate(
                $this->resourceConfig->getTable('core_config_data'),
                [
                    'scope_id' => $config['scope_id'],
                    'scope' => $config['scope'],
                    'value' => '-',
                    'path' => 'amasty_shopby_seo/url/special_char'
                ]
            );
        }

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
