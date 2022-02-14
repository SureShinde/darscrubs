<?php

namespace Amasty\GroupedOptions\Model\ResourceModel;

use Amasty\GroupedOptions\Api\Data\GroupAttrValueInterface;
use Amasty\GroupedOptions\Api\GroupRepositoryInterface;

class GroupAttrValue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(GroupRepositoryInterface::TABLE_VALUES, GroupAttrValueInterface::ID);
    }
}
