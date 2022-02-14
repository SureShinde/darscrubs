<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\Source\GroupForm;

use Amasty\GroupedOptions\Model\Product\Attribute\GetUsedForGroups;
use Magento\Framework\Data\OptionSourceInterface;

class Attribute implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var GetUsedForGroups
     */
    private $getUsedForGroups;

    public function __construct(GetUsedForGroups $getUsedForGroups)
    {
        $this->getUsedForGroups = $getUsedForGroups;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->attributes === null) {
            $this->attributes = [];

            foreach ($this->getUsedForGroups->execute() as $item) {
                $this->attributes[$item->getId()] = $item->getFrontendLabel();
            }
        }

        return $this->attributes;
    }
}
