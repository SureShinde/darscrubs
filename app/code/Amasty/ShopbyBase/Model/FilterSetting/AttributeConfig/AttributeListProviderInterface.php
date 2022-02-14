<?php

namespace Amasty\ShopbyBase\Model\FilterSetting\AttributeConfig;

interface AttributeListProviderInterface
{
    /**
     * Getting list of attribute codes, which can be configured with Amasty Attribute Settings
     * @return array
     */
    public function getAttributeList();
}
