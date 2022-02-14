<?php

namespace Amasty\Shopby\Block\Navigation\Widget;

interface WidgetInterface
{
    /**
     * @param \Amasty\ShopbyBase\Api\Data\FilterSettingInterface $filterSetting
     *
     * @return mixed
     */
    public function setFilterSetting(\Amasty\ShopbyBase\Api\Data\FilterSettingInterface $filterSetting);

    /**
     * @return mixed
     */
    public function getFilterSetting();
}
