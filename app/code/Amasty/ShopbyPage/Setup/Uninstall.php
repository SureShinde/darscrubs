<?php

declare(strict_types=1);

namespace Amasty\ShopbyPage\Setup;

use Amasty\ShopbyPage\Api\Data\PageInterface;
use Amasty\ShopbyPage\Model\Data\Page;
use Amasty\ShopbyPage\Model\ResourceModel\Page\Collection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Delete tables manually, because Amasty_Base restricts to delete Amasty tables by Declarative Scheme.
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
        $defaultConnection->dropTable($setup->getTable(PageInterface::TABLE_NAME));
        $defaultConnection->dropTable($setup->getTable(Collection::PAGE_STORE_TABLE));
    }

    /**
     * Delete stored images
     */
    private function deleteImages(): void
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDir->delete(Page::IMAGES_DIR);
    }
}
