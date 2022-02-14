<?php

namespace Amasty\ShopbyBase\Controller\Adminhtml;

/**
 * Class Option
 */
abstract class Option extends \Magento\Backend\App\Action
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_ShopbyBase::option');
    }
}
