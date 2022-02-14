<?php

namespace Amasty\GroupedOptions\Ui\DataProvider\Group\Listing;

use Amasty\GroupedOptions\Model\GroupAttr\StoreLabelResolver;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\CollectionFactory;
use Magento\Store\Model\Store;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var StoreLabelResolver
     */
    private $storeLabelResolver;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StoreLabelResolver $storeLabelResolver,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->storeLabelResolver = $storeLabelResolver;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }

        $items = $this->getCollection()->toArray();
        foreach ($items['items'] as &$item) {
            $item['name'] = $this->storeLabelResolver->chooseStoreLabel($item['name'], Store::DEFAULT_STORE_ID);
        }

        return $items;
    }
}
