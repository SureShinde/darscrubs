<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Helper;

use Amasty\ShopbyBase\Api\Data\OptionSettingRepositoryInterface;
use Amasty\ShopbyBase\Helper\FilterSetting as FilterSettingHelper;
use Amasty\ShopbyBase\Helper\OptionSetting as OptionSettingHelper;
use Amasty\ShopbyBase\Model\ResourceModel\OptionSetting\CollectionFactory as OptionCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;

class Data extends AbstractHelper
{
    const DEFAULT_CATEGORY_LOGO_SIZE = 30;

    const PATH_BRAND_URL_KEY = 'amshopby_brand/general/url_key';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var OptionCollectionFactory
     */
    private $optionCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductUrl
     */
    private $productUrl;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var array
     */
    private $brandAliases = [];

    /**
     * @var \Amasty\ShopbyBrand\Model\Attribute
     */
    private $brandAttribute;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        OptionCollectionFactory $optionCollectionFactory,
        StoreManagerInterface $storeManager,
        ProductUrl $productUrl,
        \Magento\Framework\Escaper $escaper,
        \Amasty\ShopbyBrand\Model\Attribute $brandAttribute
    ) {
        parent::__construct($context);
        $this->url = $context->getUrlBuilder();
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->storeManager = $storeManager;
        $this->productUrl = $productUrl;
        $this->escaper = $escaper;
        $this->brandAttribute = $brandAttribute;
    }

    /**
     * @param null $scopeCode
     * @return string
     */
    public function getAllBrandsUrl($scopeCode = null)
    {
        return $this->url->getUrl($this->getIdentifier($scopeCode));
    }

    /**
     * @param $scopeCode
     * @return string
     */
    private function getIdentifier($scopeCode)
    {
        $pageIdentifier = $this->scopeConfig->getValue(
            'amshopby_brand/general/brands_page',
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
        $identifierWithId = explode('|', $pageIdentifier);

        return $identifierWithId[0];
    }

    /**
     * @return \Magento\Eav\Api\Data\AttributeOptionInterface[]|null
     * @deprecated
     */
    public function getBrandOptions()
    {
        return $this->brandAttribute->getOptions();
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getBrandAliases($storeId = null)
    {
        if (empty($this->brandAliases)) {
            $attributeCode = $this->getBrandAttributeCode();

            if ($attributeCode == '') {
                return [];
            }

            $options = $this->brandAttribute->getOptions();

            if (empty($options)) {
                return [];
            }

            $items = [];

            foreach ($options as $option) {
                $items[$option->getValue()] = str_replace(
                    '-',
                    $this->getSpecialChar(),
                    $this->productUrl->formatUrlKey($option->getLabel())
                );
            }

            $this->brandAliases = $this->getStoreAliases($items, $storeId ?: $this->storeManager->getStore()->getId());
        }

        return $this->brandAliases;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        $suffix = '';

        if ($this->scopeConfig->isSetFlag('amasty_shopby_seo/url/add_suffix_shopby')) {
            $suffix = $this->scopeConfig
                ->getValue('catalog/seo/category_url_suffix', ScopeInterface::SCOPE_STORE);
        }

        return $suffix;
    }

    /**
     * @param $filters
     * @return int|string|null
     */
    public function getBrandAttributeKey($filters)
    {
        $brandAttributeCode = $this->getBrandAttributeCode();

        foreach ($filters as $key => $filter) {
            $filterAttribute = $filter['setting']->getData('attribute_model');

            if ($filterAttribute && $filterAttribute->getAttributeCode() == $brandAttributeCode) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getBrandAttributeCode()
    {
        return $this->scopeConfig->getValue(
            'amshopby_brand/general/attribute_code',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getBrandUrlKey()
    {
        return $this->scopeConfig->getValue(
            self::PATH_BRAND_URL_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param Option $option
     * @param int $storeId
     * @param bool $addBaseUrl
     * @return string
     */
    public function getBrandUrl(Option $option, $storeId = null, $addBaseUrl = true)
    {
        $url = '#';
        $aliases = $this->getBrandAliases($storeId);

        if (isset($aliases[$option->getValue()])) {
            $brandAlias = $aliases[$option->getValue()];
            $urlKey = $this->getBrandUrlKey();
            $urlSuffix = $this->getSuffix();
            $url = (!!$urlKey ? $urlKey . '/' . $brandAlias : $brandAlias) . $urlSuffix;
            $url = $addBaseUrl ? $this->_urlBuilder->getBaseUrl() . $url : $url;
        }

        return $url;
    }

    /**
     * @param array $defaultAliases
     * @param int $storeId
     * @return array
     */
    private function getStoreAliases($defaultAliases, $storeId)
    {
        $storeIds = [
            \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            $storeId
        ];
        $filterCode =  FilterSettingHelper::ATTR_PREFIX . $this->getBrandAttributeCode();
        $collection = $this->optionCollectionFactory->create();
        $collection->addFieldToFilter('filter_code', ['eq' => $filterCode])
            ->addFieldToFilter('value', ['in' => array_keys($defaultAliases)])
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('url_alias', ['neq' => ''])
            ->setOrder('store_id', AbstractDb::SORT_ORDER_ASC);

        foreach ($collection as $item) {
            $formatAlias = $this->productUrl->formatUrlKey($item->getUrlAlias());
            $defaultAliases[$item->getValue()] = str_replace('-', $this->getSpecialChar(), $formatAlias);
        }

        return $defaultAliases;
    }

    public function getModuleConfig(string $path): string
    {
        return $this->scopeConfig->getValue('amshopby_brand/' . $path, ScopeInterface::SCOPE_STORE) ?: '';
    }

    /**
     * @return int|string
     */
    public function isTopmenuEnabled()
    {
        return $this->getModuleConfig('general/topmenu_enabled');
    }

    /**
     * @param array|\Amasty\ShopbyBrand\Model\Brand\BrandDataInterface $item
     * @return string
     */
    public function generateToolTipContent($item)
    {
        $content = '';
        $template = $this->getModuleConfig('general/tooltip_content');
        $template = $this->replaceCustomVariables($template, $item);
        $template = trim($template);

        if ($template) {
            $template = $this->escaper->escapeHtml($template);
            $content = 'data-amshopby-js="brand-tooltip" data-tooltip-content="' . $template . '"';
        }

        return $content;
    }

    /**
     * @param string $template
     * @param array|\Amasty\ShopbyBrand\Model\Brand\BrandDataInterface $item
     * @return string
     */
    private function replaceCustomVariables($template, $item)
    {
        preg_match_all('@\{(.+?)\}@', $template, $matches);

        if (isset($matches[1]) && is_array($matches[1])) {
            foreach ($matches[1] as $match) {
                $value = '';
                switch ($match) {
                    case 'title':
                        if (isset($item['label'])) {
                            $value = '<h3>' . $item['label'] . '</h3>';
                        }
                        break;
                    case 'description':
                    case 'short_description':
                        if (isset($item[$match]) && $item[$match]) {
                            $value = '<p>' . $item[$match] . '</p>';
                        }
                        break;
                    case 'small_image':
                    case 'image':
                        $imgUrl = $match == 'small_image' ? $item['img'] : $item['image'];
                        if (isset($imgUrl)) {
                            $value = '<img class="am-brand-' . $match . '" src="' . $imgUrl . '"/>';
                        }
                        break;
                }
                $template = str_replace('{' . $match . '}', $value, $template);
            }
        }

        return strip_tags($template, '<img><p><h3><b><strong>');
    }

    /**
     * @return string
     */
    public function getSpecialChar()
    {
        return $this->_moduleManager->isEnabled('Amasty_ShopbySeo')
            ? $this->scopeConfig->getValue('amasty_shopby_seo/url/special_char', ScopeInterface::SCOPE_STORE)
            : '-';
    }

    /**
     * @return string
     */
    public function getBrandLabel()
    {
        return (string) $this->scopeConfig->getValue(
            'amshopby_brand/general/menu_item_label',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * used in graphQL
     * @return int|string
     */
    public function getLogoProductPageWidth()
    {
        return $this->getModuleConfig('product_page/width');
    }

    /**
     * used in graphQL
     * @return int|string
     */
    public function getLogoProductPageHeight()
    {
        return $this->getModuleConfig('product_page/height');
    }

    /**
     * @return int|string
     */
    public function getBrandLogoProductListingWidth()
    {
        $width = $this->getModuleConfig('product_listing_settings/listing_brand_logo_width');

        return $width ?: self::DEFAULT_CATEGORY_LOGO_SIZE;
    }

    /**
     * @return int|string
     */
    public function getBrandLogoProductListingHeight()
    {
        $height = $this->getModuleConfig('product_listing_settings/listing_brand_logo_height');

        return $height ?: self::DEFAULT_CATEGORY_LOGO_SIZE;
    }
}
