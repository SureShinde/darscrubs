<?php

namespace Amasty\Shopby\Block\Navigation;

class Search extends \Magento\LayeredNavigation\Block\Navigation
{
    /**
     * @return $this|\Magento\Framework\View\Element\AbstractBlock|\Magento\Framework\View\Element\Template
     */
    protected function _beforeToHtml()
    {
        return $this;
    }
}
