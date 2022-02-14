<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement as AbstractElement;

class Multiselect extends Field
{
    const DEFAULT = 10;

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('size', count($element->getValues()) ?: self::DEFAULT);
        return $element->getElementHtml();
    }
}
