<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SliderSort implements OptionSourceInterface
{
    const NAME = 'name';
    const POSITION = 'position';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::NAME, 'label' => __('Name')],
            ['value' => self::POSITION, 'label' => __('Position')]
        ];
    }

    public function toArray(): array
    {
        return [self::NAME => __('Name'), self::POSITION => __('Position')];
    }
}
