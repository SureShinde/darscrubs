<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel;

use Amasty\GroupedOptions\Api\GroupRepositoryInterface;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr as GroupAttrResource;

class GroupRepository implements GroupRepositoryInterface
{
    /**
     * @var GroupAttr
     */
    protected $groupAttr;

    public function __construct(GroupAttrResource $groupAttr)
    {
        $this->groupAttr = $groupAttr;
    }

    /**
     * @param $groupCode
     * @return array|false
     */
    public function getGroupOptionsIds($groupCode)
    {
        $select = $this->groupAttr->getConnection()->select()->from(
            ['group' => $this->groupAttr->getTable(GroupRepositoryInterface::TABLE)],
            ''
        )->where('group.group_code = ?', $groupCode);
        $select->joinLeft(
            ['option' => $this->groupAttr->getTable(GroupRepositoryInterface::TABLE_OPTIONS)],
            'option.group_id=group.group_id',
            ['option.option_id']
        );
        return $this->groupAttr->getConnection()->fetchCol($select);
    }
}
