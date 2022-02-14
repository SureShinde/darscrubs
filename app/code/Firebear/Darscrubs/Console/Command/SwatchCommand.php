<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Firebear\Darscrubs\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

use Firebear\Darscrubs\Helper\ImportColorSwatch;

/**
 * Command to run indexers
 */
class SwatchCommand extends Command
{
    const NAME = 'command';
    const ACTION = 'action';
    const TEST = 'test';
    const LOG = 'log';

    protected $importColorSwatch;

    public function __construct(
        ImportColorSwatch $importColorSwatch
    ) {
        $this->importColorSwatch = $importColorSwatch;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('swatch:exe')
            ->setDescription('Swatch Exe');
        $this->addOption(
            self::NAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Name'
        );
        $this->addOption(
            self::ACTION,
            null,
            InputOption::VALUE_OPTIONAL,
            'Action'
        );
        $this->addOption(
            self::TEST,
            null,
            InputOption::VALUE_OPTIONAL,
            'Test'
        );
        $this->addOption(
            self::LOG,
            null,
            InputOption::VALUE_OPTIONAL,
            'Log'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$input->getArgument('command');
        $command = $input->getOption(self::NAME);

        $returnValue = Cli::RETURN_FAILURE;

        try {
            $startTime = microtime(true);

            switch ($command) {
                case 'remove_unused':
                    $output->write("[Start Swatch Remove Unused] ");
                    $this->importColorSwatch->removeUnUsed();
                    break;
                case 'remove_duplicates':
                    $output->write("[Start Swatch Remove Duplicates] ");
                    $this->importColorSwatch->removeDuplicates();
                    break;
                case 'fix_type_length':
                    $output->write("[Start Fix Type Length] ");
                    $this->importColorSwatch->fixTypeLength();
                    break;
                case 'fix_config_prod_sku':
                    $output->write("[Start Fix Config Prod SKU] ");
                    $this->importColorSwatch->fixConfigProdSku();
                    break;
                case 'fix_duplicates_sku':
                    $output->write("[Start Fix_Duplicates_Sku] ");
                    $this->importColorSwatch->fixDuplicatesSku($input);
                    break;
                case 'fix_config_prod_validation':
                    $output->write("[Start fix_config_prod_validation] ");
                    $this->importColorSwatch->fixConfigProdValidation();
                    break;
                case 'fix_config_attr_validation':
                    $output->write("[Start fix_config_attr_validation] ");
                    $this->importColorSwatch->fixConfigAttrValidation($input);
                    break;
                case 'fix_gender':
                    $output->write("[Start fix_gender] ");
                    $this->importColorSwatch->fixGender($input);
                    break;
                case 'check_config_prod_child':
                    $output->write("[Start check_config_prod_child] ");
                    $this->importColorSwatch->checkConfigProdChild($input);
                    break;
                case 'list_sku_missing_image':
                    $output->write("[Start list_sku_missing_image] ");
                    $this->importColorSwatch->listSkuMissingImage($input);
                    break;
                case 'fix_image':
                    $output->write("[Start fix_image] ");
                    $this->importColorSwatch->fixImage($input);
                    break;
                case 'remove_orphans':
                    $output->write("[Start remove_orphans] ");
                    $this->importColorSwatch->removeOrphans($input);
                    break;
                case 'check_url_without_sku':
                    $output->write("[Start check_url_without_sku] ");
                    $this->importColorSwatch->checkUrlWithoutSku($input);
                    break;
                case 'link_child_to_configurable':
                    $output->write("[Start link_child_to_configurable] ");
                    $this->importColorSwatch->linkChildToConfigurable($input);
                    break;
                case 'set_length_regular_for_child':
                    $output->write("[Start set_length_regular_for_child] ");
                    $this->importColorSwatch->setLengthRegularForChild($input);
                    break;
                case 'add_length_attr_for_bottom_config':
                    $output->write("[Start add_length_attr_for_bottom_config] ");
                    $this->importColorSwatch->addLengthAttrForBottomConfig($input);
                    break;
                default:
                    echo "no_command";
                    break;
            }

            $resultTime = microtime(true) - $startTime;

            $output->writeln(
                __('has been done successfully in %time', ['time' => gmdate('H:i:s', $resultTime)])
            );
            $returnValue = Cli::RETURN_SUCCESS;
        } catch (LocalizedException $e) {
            $output->writeln(__('exception: %message', ['message' => $e->getMessage()]));
        } catch (\Exception $e) {
            $output->writeln('process unknown error:');
            $output->writeln($e->getMessage());

            $output->writeln($e->getTraceAsString(), OutputInterface::VERBOSITY_DEBUG);
        }

        return $returnValue;
    }

}
