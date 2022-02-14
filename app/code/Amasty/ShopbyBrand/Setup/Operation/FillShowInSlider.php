<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup\Operation;

use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\CollectionFactory;

class FillShowInSlider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(): void
    {
        $collection = $this->collectionFactory->create();
        foreach ($collection as $option) {
            $option->setIsShowInSlider($option->getIsFeatured());
        }
        $collection->save();
    }
}
