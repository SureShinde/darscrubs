<?php

namespace Amasty\ShopbyBrand\Ui\Component\Listing\Columns;

/**
 * Class SliderImage
 * @package Amasty\ShopbyBrand\Ui\Component\Listing\Columns
 */
class SliderImage extends Image
{
    /**
     * @param \Amasty\ShopbyBase\Api\Data\OptionSettingInterface $brand
     * @return null|string
     */
    protected function getImage(\Amasty\ShopbyBase\Api\Data\OptionSettingInterface $brand)
    {
        return $brand->getSliderImageUrl()
            ? $brand->getSliderImageUrl()
            : $this->imageHelper->getDefaultPlaceholderUrl();
    }
}
