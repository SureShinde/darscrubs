<?php
namespace Amasty\ShopbyBase\Model\Customizer\Category;

use Magento\Catalog\Model\Category;

interface CustomizerInterface
{
    public function prepareData(Category $category);
}
