<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel\GroupAttrValue;

use Amasty\GroupedOptions\Model\GroupAttrValue as GroupAttrValueModel;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrValue as GroupAttrValueResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'group_option_id';

    protected function _construct()
    {
        $this->_init(GroupAttrValueModel::class, GroupAttrValueResource::class);
    }
}
