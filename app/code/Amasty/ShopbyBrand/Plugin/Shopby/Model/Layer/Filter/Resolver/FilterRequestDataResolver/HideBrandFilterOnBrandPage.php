<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Plugin\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver as FilterDataResolver;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBrand\Helper\Content;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;

class HideBrandFilterOnBrandPage
{
    /**
     * @var  Content
     */
    protected $contentHelper;

    public function __construct(Content $contentHelper)
    {
        $this->contentHelper = $contentHelper;
    }
    
    public function afterIsVisibleWhenSelected(FilterDataResolver $subject, bool $result, FilterInterface $filter): bool
    {
        return ($result && $this->isBrandingBrand($filter)) ? false : $result;
    }

    private function isBrandingBrand(FilterInterface $subject): bool
    {
        $brand = $this->contentHelper->getCurrentBranding();
        return $brand && (FilterSetting::ATTR_PREFIX . $subject->getRequestVar() == $brand->getFilterCode());
    }
}
