<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\Source\GroupForm;

use Amasty\GroupedOptions\Model\Product\Attribute\BuildOptionsArray;
use Amasty\GroupedOptions\Model\Product\Attribute\GetUsedForGroups;
use Magento\Framework\Data\OptionSourceInterface;

class AttributeOption implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var GetUsedForGroups
     */
    private $getUsedForGroups;

    /**
     * @var BuildOptionsArray
     */
    private $buildOptionsArray;

    public function __construct(
        GetUsedForGroups $getUsedForGroups,
        BuildOptionsArray $buildOptionsArray
    ) {
        $this->getUsedForGroups = $getUsedForGroups;
        $this->buildOptionsArray = $buildOptionsArray;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $attributes = $this->getUsedForGroups->execute();
            $this->options = $this->buildOptionsArray->execute($attributes);
        }

        return $this->options;
    }
}
