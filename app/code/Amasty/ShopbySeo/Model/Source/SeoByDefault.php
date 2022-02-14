<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\Source;

class SeoByDefault implements \Magento\Framework\Data\OptionSourceInterface
{
    const GENERATED = 1;
    const NOT_GENERATED = 0;

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::GENERATED,
                'label' => __('Generated')
            ],
            [
                'value' => self::NOT_GENERATED,
                'label' => __('Not Generated')
            ],
        ];
    }
}
