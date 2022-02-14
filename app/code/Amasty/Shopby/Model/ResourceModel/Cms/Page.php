<?php

namespace Amasty\Shopby\Model\ResourceModel\Cms;

use Amasty\Shopby\Api\CmsPageRepositoryInterface;

class Page extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CmsPageRepositoryInterface::TABLE, 'entity_id');
    }
}
