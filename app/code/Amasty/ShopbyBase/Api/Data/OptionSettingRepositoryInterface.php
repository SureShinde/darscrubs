<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Api\Data;

use Magento\Framework\Exception\NoSuchEntityException;

interface OptionSettingRepositoryInterface
{
    const TABLE = 'amasty_amshopby_option_setting';

    /**
     * @return OptionSettingInterface
     * @throws NoSuchEntityException
     */
    public function get($value, $field = null);

    /**
     * @param string $filterCode
     * @param int $optionId
     * @param int $storeId
     * @return OptionSettingInterface
     */
    public function getByParams($filterCode, $optionId, $storeId);

    /**
     * @param OptionSettingInterface $optionSetting
     * @return OptionSettingRepositoryInterface
     */
    public function save(OptionSettingInterface $optionSetting);

    /**
     * @param int $storeId
     * @return array
     */
    public function getAllFeaturedOptionsArray($storeId);

    /**
     * @param int $optionId
     * @return void
     */
    public function deleteByOptionId(int $optionId);
}
