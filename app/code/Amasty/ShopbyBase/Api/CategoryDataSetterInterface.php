<?php

namespace Amasty\ShopbyBase\Api;

use Magento\Catalog\Model\Category;

interface CategoryDataSetterInterface
{
    const APPLIED_BRAND_VALUE = 'applied_brand_customizer_value';

    public function setCategoryData(Category $category);
}
