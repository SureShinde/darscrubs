<?php
namespace Onesaas\Connect\Block\Adminhtml\Key;

use Magento\Backend\Block\Template;

class CustomBlock extends Template
{	
	/*
	public function _prepareLayout()
	{
		return parent::_prepareLayout();
	}*/
	
	protected function _construct()
	{
		$this->headerText = __('Onesaas Connect Api Key');
		parent::_construct();
	}
	
}
