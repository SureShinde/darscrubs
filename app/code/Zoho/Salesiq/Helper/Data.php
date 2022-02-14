<?php

namespace Zoho\Salesiq\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const WIDGET_STAUS = 'widget_config/option_group/isallowed';
    const SCRIPT_CODE = 'widget_config/option_group/salesiqscript';


    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }


    public function isScriptEmbedEnabled()
    {
        return $this->scopeConfig->getValue(
            self::WIDGET_STAUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSalesiqScript()
    {
        return $this->scopeConfig->getValue(
            self::SCRIPT_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
