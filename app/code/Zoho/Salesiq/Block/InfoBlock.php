<?php
namespace Zoho\Salesiq\Block;
class InfoBlock extends \Magento\Framework\View\Element\Template
{

    protected $siqModuleHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Zoho\Salesiq\Helper\Data $siqModuleHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_siqmoduleHelper = $siqModuleHelper;
    }

    public function isScriptEmbedEnabled()
    {
        return $this->_siqmoduleHelper->isScriptEmbedEnabled();
    }

    public function getSalesiqScript()
    {
        return $this->_siqmoduleHelper->getSalesiqScript();
    }
}
?>
