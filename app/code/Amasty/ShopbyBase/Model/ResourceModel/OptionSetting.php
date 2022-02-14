<?php

namespace Amasty\ShopbyBase\Model\ResourceModel;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use \Magento\Store\Model\Store;

class OptionSetting extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * OptionSetting protected constructor
     */
    protected function _construct()
    {
        $this->_init(OptionSettingRepositoryInterface::TABLE, OptionSettingInterface::OPTION_SETTING_ID);
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getAllFeaturedOptionsArray($storeId)
    {
        $options = [];
        $select = $this->getConnection()->select()->from(
            ['main_table' => $this->getMainTable()],
            [$this->getIdFieldName(), 'value', 'store_id', 'filter_code', 'is_featured']
        )->where(
            'store_id IN(?)',
            [Store::DEFAULT_STORE_ID, $storeId]
        )->where('is_featured = 1');

        $result = $this->getConnection()->fetchAll($select);
        foreach ($result as $option) {
            $options[$option['filter_code']][$option['value']][$option['store_id']] = true;
        }

        return $options;
    }
}
