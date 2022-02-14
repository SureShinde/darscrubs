<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model;

use Amasty\GroupedOptions\Api\Data\GroupAttrOptionInterface;
use Amasty\GroupedOptions\Model\ResourceModel\GroupAttrOption as GroupAttrOptionResource;
use Magento\Framework\Model\AbstractModel;

class GroupAttrOption extends AbstractModel implements GroupAttrOptionInterface
{
    protected function _construct()
    {
        $this->_init(GroupAttrOptionResource::class);
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
     * @return int
     */
    public function getOptionId()
    {
        return $this->getData(self::OPTION_ID);
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
     * @return GroupAttrOptionInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @param $id
     * @return GroupAttrOptionInterface
     */
    public function setGroupId($id)
    {
        return $this->setData(self::GROUP_ID, $id);
    }

    /**
     * @param $option
     * @return GroupAttrOptionInterface
     */
    public function setOptionId($option)
    {
        return $this->setData(self::OPTION_ID, $option);
    }

    /**
     * @param $sort
     * @return GroupAttrOptionInterface
     */
    public function setSortOrder($sort)
    {
        return $this->setData(self::SORT_ORDER, $sort);
    }
}
