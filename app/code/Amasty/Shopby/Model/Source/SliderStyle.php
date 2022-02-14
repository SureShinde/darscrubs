<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Source;

class SliderStyle implements \Magento\Framework\Option\ArrayInterface
{
    const DEFAULT = '-default';

    const IMPROVED = '-improved';

    const VOLUMETRIC_GRADIENT = '-volumetric';

    const LIGHT = '-light';

    const DARK = '-dark';

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->toArray() as $optionValue => $optionLabel) {
            $options[] = [
                'value' => $optionValue,
                'label' => $optionLabel
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::DEFAULT => __('Default'),
            self::IMPROVED => __('Improved'),
            self::VOLUMETRIC_GRADIENT => __('Volumetric Gradient'),
            self::LIGHT => __('Light'),
            self::DARK => __('Dark')
        ];
    }
}
