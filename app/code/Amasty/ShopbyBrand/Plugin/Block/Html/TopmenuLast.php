<?php
namespace Amasty\ShopbyBrand\Plugin\Block\Html;

use Amasty\ShopbyBrand\Model\Source\TopmenuLink as TopmenuSource;

/**
 * Class TopmenuLast
 *
 * @package Amasty\ShopbyBrand\Plugin\Block\Html
 */
class TopmenuLast extends \Amasty\ShopbyBrand\Plugin\Block\Html\Topmenu
{
    /**
     * @return int
     */
    protected function getPosition()
    {
        return TopmenuSource::DISPLAY_LAST;
    }
}
