<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice;

use Amasty\Shopby\Model\ResourceModel\Catalog\Product\Indexer\Price\DefaultPrice as DefaultPriceResource;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice;

class AddIndexSpecialPrice
{
    /**
     * @var DefaultPriceResource
     */
    private $defaultPriceResource;

    public function __construct(DefaultPriceResource $defaultPriceResource)
    {
        $this->defaultPriceResource = $defaultPriceResource;
    }

    /**
     * @param DefaultPrice $subject
     * @param mixed$result
     * @return mixed
     */
    public function afterReindexAll($subject, $result)
    {
        $this->defaultPriceResource->addSpecialPrice($subject->getIdxTable());

        return $result;
    }

    /**
     * @param DefaultPrice $subject
     * @param mixed $result
     * @param array $entityIds
     * @return mixed
     */
    public function afterReindexEntity($subject, $result, $entityIds)
    {
        $this->defaultPriceResource->addSpecialPrice($subject->getIdxTable(), $entityIds);

        return $result;
    }
}
