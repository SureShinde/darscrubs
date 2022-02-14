<?php

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby;
use \Amasty\ShopbyBase\Model\FilterSetting;

class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * @var  Shopby\Helper\UrlBuilder
     */
    private $urlBuilderHelper;

    /**
     * @var FilterSetting
     */
    private $filterSetting;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        Shopby\Helper\UrlBuilder $urlBuilderHelper,
        FilterSetting $filterSetting,
        array $data = []
    ) {
        parent::__construct($url, $htmlPagerBlock, $data);
        $this->urlBuilderHelper = $urlBuilderHelper;
        $this->filterSetting = $filterSetting;
    }

    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->urlBuilderHelper->buildUrl($this->getFilter(), $this->getValue());
    }

    /**
     * Get url for remove item from filter
     * @param mixed $value
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRemoveUrl($value = null)
    {
        $value = $value ?? $this->getValue();

        return $this->urlBuilderHelper->buildUrl($this->getFilter(), $value);
    }

    /**
     * @return bool
     */
    public function isAddNofollow()
    {
        return $this->filterSetting->isAddNofollow();
    }

    /**
     * @return string
     */
    public function getOptionLabel()
    {
        return $this->getData('label');
    }
}
