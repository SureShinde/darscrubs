<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\Product\Attribute;

use Amasty\GroupedOptions\Model\ResourceModel\Product\Attribute\LoadUsedForGroups;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class GetUsedForGroups
{
    /**
     * @var LoadUsedForGroups
     */
    private $loadUsedForGroups;

    /**
     * @var Attribute[]
     */
    private $attributes;

    public function __construct(LoadUsedForGroups $loadUsedForGroups)
    {
        $this->loadUsedForGroups = $loadUsedForGroups;
    }

    /**
     * @param array|null $filterIds
     * @return Attribute[]
     */
    public function execute(?array $filterIds = null): array
    {
        if ($this->attributes === null) {
            $this->attributes = $this->loadUsedForGroups->execute($filterIds);
        }

        return $this->attributes;
    }
}
