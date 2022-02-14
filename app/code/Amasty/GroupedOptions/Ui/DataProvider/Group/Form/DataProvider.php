<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\DataProvider\Group\Form;

use Amasty\GroupedOptions\Model\Backend\Group\Registry as GroupRegistry;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var array|null
     */
    private $loadedData;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var GroupRegistry
     */
    private $groupRegistry;

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        DataPersistorInterface $dataPersistor,
        GroupRegistry $groupRegistry,
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Manager $moduleManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->dataPersistor = $dataPersistor;
        $this->groupRegistry = $groupRegistry;
        $this->moduleManager = $moduleManager;
    }

    public function getData(): array
    {
        if ($this->loadedData === null) {
            $group = $this->groupRegistry->getGroup();
            $this->loadedData[$group->getId()] = $group->getData();
        }

        return $this->loadedData;
    }

    public function getMeta()
    {
        $meta = parent::getMeta();
        if (!$this->moduleManager->isEnabled('Amasty_Shopby')) {
            $meta['general']['children']['url']['arguments']['data']['config']['visible'] = false;
        }

        return  $meta;
    }
}
