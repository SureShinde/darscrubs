<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Setup;

use Amasty\ShopbyBase\Api\Data\FilterSettingRepositoryInterface;
use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use Amasty\ShopbyBase\Model\OptionSetting;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Delete tables manually, because Amasty_Base restricts to delete Amasty tables by Declarative Scheme.
 *
 * @see \Amasty\Base\Plugin\Framework\Setup\Declaration\Schema\Diff\Diff\RestrictDropTables
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->deleteImages();

        $defaultConnection = $setup->getConnection();
        $defaultConnection->dropTable(
            $setup->getTable(FilterSettingRepositoryInterface::TABLE)
        );
        $defaultConnection->dropTable(
            $setup->getTable(OptionSettingRepositoryInterface::TABLE)
        );
    }

    /**
     * Delete stored images
     */
    private function deleteImages(): void
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDir->delete(OptionSetting::IMAGES_DIR);
    }
}
