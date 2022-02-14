<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Plugin\Catalog\Model\ResourceModel\Product\Collection;

use Amasty\ShopbyBrand\Helper\Content;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class FixUrlRewrites
{
    /**
     * @var  Content
     */
    private $contentHelper;

    public function __construct(Content $contentHelper)
    {
        $this->contentHelper = $contentHelper;
    }

    /**
     * @param Collection $subject
     * @param int|string $categoryId
     * @return array
     * @see Collection::addUrlRewrite()
     */
    public function beforeAddUrlRewrite(Collection $subject, $categoryId = ''): array
    {
        if ($this->contentHelper->getCurrentBranding()) {
            $categoryId = 0;
        }

        return [$categoryId];
    }
}
