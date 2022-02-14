<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model;

use Amasty\GroupedOptions\Api\Data\GroupAttrValueInterface;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrValue as GroupAttrValueResource;
use Magento\Framework\Model\AbstractModel;

class GroupAttrValue extends AbstractModel implements GroupAttrValueInterface
{

    protected function _construct()
    {
        $this->_init(GroupAttrValueResource::class);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->getData(self::GROUP_ID);
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * @param $id
     * @return GroupAttrValueInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @param $id
     * @return GroupAttrValueInterface
     */
    public function setGroupId($id)
    {
        return $this->setData(self::GROUP_ID, $id);
    }

    /**
     * @param $option
     * @return GroupAttrValueInterface
     */
    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * @param $sort
     * @return GroupAttrValueInterface
     */
    public function setSortOrder($sort)
    {
        return $this->setData(self::SORT_ORDER, $sort);
    }
}
