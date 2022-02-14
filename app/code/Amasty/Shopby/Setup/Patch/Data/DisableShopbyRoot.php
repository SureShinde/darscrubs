<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\Status;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DisableShopbyRoot implements DataPatchInterface
{
    /**
     * @var ConfigInterface
     */
    private $resourceConfig;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Status
     */
    private $moduleStatus;

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        ConfigInterface $resourceConfig,
        Filesystem $filesystem,
        Status $moduleStatus,
        Manager $moduleManager
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->filesystem = $filesystem;
        $this->moduleStatus = $moduleStatus;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function apply()
    {
        if ($this->moduleManager->isEnabled('Amasty_ShopbyRoot')) {
            $pathToModule = $this->filesystem->getDirectoryRead('app')->getAbsolutePath()
                . 'code/Amasty/ShopbyRoot';

            try {
                $this->moduleStatus->setIsEnabled(false, ['Amasty_ShopbyRoot']);
            } catch (\Exception $e) {
                throw new LocalizedException(
                    __('Please remove "%1" folder manually.', $pathToModule)
                );
            }
        }

        $connection = $this->resourceConfig->getConnection();
        $connection->delete($this->resourceConfig->getTable('setup_module'), 'module = "Amasty_ShopbyRoot"');

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
