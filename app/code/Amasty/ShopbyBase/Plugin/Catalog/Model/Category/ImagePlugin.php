<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Plugin\Catalog\Model\Category;

use Amasty\ShopbyBase\Model\Category\Manager as CategoryManager;
use Magento\Catalog\Model\Category;

class ImagePlugin
{
    /**
     * @param $subject
     * @param string $result
     * @param Category $category
     * @param string $attributeCode
     * @return string
     */
    public function afterGetUrl(
        $subject,
        string $result,
        Category $category,
        string $attributeCode
    ): string {
        $image = $category->getData(CategoryManager::CATEGORY_SHOPBY_IMAGE_URL);

        return $image ?: $result;
    }
}
