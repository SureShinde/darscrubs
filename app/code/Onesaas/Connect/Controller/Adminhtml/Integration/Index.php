<?php

namespace Onesaas\Connect\Controller\Adminhtml\Integration;

class Index extends \Magento\Backend\App\Action
{
	public function execute()
	{
		$this->_view->loadLayout();
		$this->_view->getPage()->getConfig()->getTitle()->prepend(__('Onesaas Connect'));
		$this->_view->renderLayout();
	}
	
}
