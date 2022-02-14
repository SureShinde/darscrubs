<?php

declare(strict_types=1);

namespace Amasty\Shopby\Setup;

use Amasty\Base\Model\MagentoVersion;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    public function __construct(MagentoVersion $magentoVersion)
    {
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($this->magentoVersion->get(), '2.3.3', '<')) {
            throw new LocalizedException(
                __('Amasty Improved Layered Navigation supports Magento v.2.3.3+ only')
            );
        }
    }
}
