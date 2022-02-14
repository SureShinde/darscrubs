<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Source;

class ScrollToTop implements \Magento\Framework\Option\ArrayInterface
{
    const NO = 0;

    const TO_LISTING = 1;

    const TO_TOP = 2;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
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
            self::TO_LISTING => __('Yes (to Listing Top)'),
            self::TO_TOP => __('Yes (to Page Top)'),
            self::NO => __('No')
        ];
    }
}
