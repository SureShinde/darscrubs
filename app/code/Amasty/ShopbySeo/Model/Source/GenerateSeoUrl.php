<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\Source;

class GenerateSeoUrl implements \Magento\Framework\Data\OptionSourceInterface
{
    const USE_DEFAULT = 0;
    const YES = 1;
    const NO = 2;

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NO,
                'label' => __('No')
            ],
            [
                'value' => self::YES,
                'label' => __('Yes')
            ],
            [
                'value' => self::USE_DEFAULT,
                'label' => __('Use Default')
            ],
        ];
    }
}
