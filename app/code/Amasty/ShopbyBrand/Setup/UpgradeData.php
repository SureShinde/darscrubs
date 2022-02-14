<?php

namespace Amasty\ShopbyBrand\Setup;

use Amasty\ShopbyBrand\Setup\Operation\FillShowInSlider;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * @deprecated migrated to Declarative Scheme. The remaining algorithms cannot be safely migrated
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var FillShowInSlider
     */
    private $fillShowInSlider;

    public function __construct(
        \Magento\Framework\App\State $appState,
        FillShowInSlider $fillShowInSlider
    ) {
        $this->appState = $appState;
        $this->fillShowInSlider = $fillShowInSlider;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->appState->emulateAreaCode(
            \Magento\Framework\App\Area::AREA_ADMINHTML,
            [$this, 'upgradeCallback'],
            [$setup, $context]
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgradeCallback(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.10.12', '<')) {
            $this->fillShowInSlider->execute();
        }

        $setup->endSetup();
    }
}
