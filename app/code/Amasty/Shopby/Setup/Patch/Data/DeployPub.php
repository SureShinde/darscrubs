<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup\Patch\Data;

use Amasty\Base\Helper\Deploy as DeployHelper;
use Amasty\Shopby\Model\Config\Backend\Image\Tooltip;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Copy pub folder to root directory.
 */
class DeployPub implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var DeployHelper
     */
    private $deployHelper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Module\Dir
     */
    private $moduleDir;

    public function __construct(
        DeployHelper $deployHelper,
        Filesystem $filesystem,
        \Magento\Framework\Module\Dir $moduleDir
    ) {
        $this->deployHelper = $deployHelper;
        $this->filesystem = $filesystem;
        $this->moduleDir = $moduleDir;
    }

    public function apply()
    {
        $modulePath = $this->moduleDir->getDir('Amasty_Shopby');
        $modulePath .= '/pub';
        $this->deployHelper->deployFolder($modulePath);

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

    public function revert()
    {
        $rootWrite = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $rootWrite->delete(Tooltip::UPLOAD_DIR);
    }
}
