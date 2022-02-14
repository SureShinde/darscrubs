<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Setup\Model\DeclarationInstaller;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\DryRunLogger;
use Magento\Framework\Setup\Exception;
use Magento\Framework\Setup\Patch\PatchApplier;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Setup\Model\DeclarationInstaller;

class ApplyPatchesBeforeDeclarativeSchema
{
    const MODULE_NAME = 'Amasty_GroupedOptions';

    /**
     * @var PatchApplier
     */
    private $patchApplier;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        PatchApplier $patchApplier,
        ResourceConnection $resourceConnection
    ) {
        $this->patchApplier = $patchApplier;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param DeclarationInstaller $declarationInstaller
     * @param array $request
     * @return null
     * @throws Exception
     */
    public function beforeInstallSchema(
        DeclarationInstaller $declarationInstaller,
        array $request
    ): ?array {
        $isDryRun = $request[DryRunLogger::INPUT_KEY_DRY_RUN_MODE] ?? true;
        $connection = $this->resourceConnection->getConnection();

        if (!$isDryRun
            && $connection->isTableExists($this->resourceConnection->getTableName(PatchHistory::TABLE_NAME))
        ) {
            $this->patchApplier->applySchemaPatch(self::MODULE_NAME);
        }

        return null;
    }
}
