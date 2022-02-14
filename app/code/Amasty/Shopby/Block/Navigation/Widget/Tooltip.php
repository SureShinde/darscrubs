<?php

namespace Amasty\Shopby\Block\Navigation\Widget;

use Amasty\Shopby\Model\ConfigProvider;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Model\FilterSetting\StoreSettingResolver;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class Tooltip extends Template implements WidgetInterface
{
    /**
     * @var FilterSettingInterface
     */
    protected $filterSetting;

    /**
     * @var string
     */
    protected $_template = 'Amasty_Shopby::layer/widget/tooltip.phtml';

    /**
     * @var StoreSettingResolver
     */
    private $storeSettingResolver;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        StoreSettingResolver $storeSettingResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeSettingResolver = $storeSettingResolver;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @param FilterSettingInterface $filterSetting
     * @return $this
     */
    public function setFilterSetting(FilterSettingInterface $filterSetting)
    {
        $this->filterSetting = $filterSetting;
        return $this;
    }

    /**
     * @return FilterSettingInterface
     */
    public function getFilterSetting()
    {
        return $this->filterSetting;
    }

    public function getTooltipUrl(): string
    {
        $url = $this->configProvider->getTooltipSrc();
        if ($url) {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $url = $baseUrl . $url;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getTooltipTemplate()
    {
        return sprintf(
            '<span class="tooltip amshopby-filter-tooltip" title="{content}"><img src="%s"></span>',
            $this->escapeUrl($this->getTooltipUrl())
        );
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return null|string
     */
    public function getContent()
    {
        if ($tooltip = $this->getFilterSetting()->getTooltip()) {
            $tooltip = strip_tags($this->storeSettingResolver->chooseStoreLabel($tooltip));
        }

        return $tooltip;
    }

    /*
     * @param  mixed $valueToEncode
     * @param  boolean $cycleCheck
     * @param  array $options
     * @return string
     */
    /**
     * @param $valueToEncode
     * @param bool $cycleCheck
     * @param array $options
     * @return string
     */
    public function jsonEncode($valueToEncode, $cycleCheck = false, $options = [])
    {
        return \Zend_Json::encode($valueToEncode, $cycleCheck, $options);
    }
}
