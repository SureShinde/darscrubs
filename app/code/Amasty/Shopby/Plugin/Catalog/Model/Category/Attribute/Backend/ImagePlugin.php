<?php

namespace Amasty\Shopby\Plugin\Catalog\Model\Category\Attribute\Backend;

use Magento\Catalog\Model\Category\Attribute\Backend\Image;
use Amasty\Shopby\Plugin\Catalog\Model\Category;

class ImagePlugin
{
    /**
     * Fix for bad urls on 2.3.4
     * @param Image $subject
     * @param \Closure $proceed
     * @param $category
     */
    public function aroundBeforeSave(Image $subject, \Closure $proceed, $category)
    {
        $attributeName = $subject->getAttribute()->getName();
        $thumbnailFiles = $category->getThumbnail();
        $proceed($category);

        if (isset($thumbnailFiles[0]['name']) && $attributeName == Category::THUMBNAIL) {
            $category->setThumbnail($thumbnailFiles[0]['name']);
        }
    }
}
