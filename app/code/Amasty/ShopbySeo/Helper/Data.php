<?php

namespace Amasty\ShopbySeo\Helper;

use Amasty\Base\Model\Serializer;
use Amasty\ShopbySeo\Model\Source\GenerateSeoUrl;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Amasty\ShopbyBase\Model\ResourceModel\FilterSetting\CollectionFactory;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Data extends AbstractHelper
{
    const CANONICAL_ROOT = 'amasty_shopby_seo/canonical/root';
    const CANONICAL_CATEGORY = 'amasty_shopby_seo/canonical/category';
    const AMASTY_SHOPBY_SEO_URL_SPECIAL_CHAR = 'amasty_shopby_seo/url/special_char';
    const AMASTY_SHOPBY_SEO_URL_ATTRIBUTE_NAME = 'amasty_shopby_seo/url/attribute_name';
    const AMASTY_SHOPBY_SEO_URL_FILTER_WORD = 'amasty_shopby_seo/url/filter_word';
    const AMSHOPBY_ROOT_GENERAL_URL = 'amshopby_root/general/url';
    const AMSHOPBY_SEO_PAGE_META_TITLE = 'amasty_shopby_seo/other/page_meta_title';
    const AMSHOPBY_SEO_PAGE_META_DESCR = 'amasty_shopby_seo/other/page_meta_descriprion';
    const AMSHOPBY_SEO_REL_NOFOLLOW = 'amasty_shopby_seo/robots/rel_nofollow';
    const SKIP_REQUEST_FLAG = 'shopby_seo_skip_request_flag';
    const SEO_REDIRECT_FLAG = 'shopby_seo_redirect_flag';
    const SEO_REDIRECT_MISSED_SUFFIX_FLAG = 'shopby_seo_missed_suffix_redirect_flag';
    const HAS_PARSED_PARAMS = 'shopby_seo_has_parsed_params_flag';
    const HAS_ROUTE_PARAMS = 'shopby_seo_has_route_params_flag';
    const IS_MODULE_ENABLED = 'amasty_shopby_seo/url/mode';

    /**
     * @var CollectionFactory
     */
    private $settingCollectionFactory;

    /**
     * @var  StoreManager
     */
    private $storeManager;

    /**
     * @var array|null
     */
    private $seoSignificantAttributeCodes;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var array
     */
    private $skipRequestIdentifiers = [
        'catalog/category/',
        'catalog/product/',
        'cms/page/',
        'amasty_xsearch/',
        'customer/',
        'checkout/',
        'catalogsearch'
    ];

    /**
     * @var array
     */
    private $attributeUrlAliases = [];

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        Context $context,
        CollectionFactory $settingCollectionFactory,
        StoreManager $storeManager,
        Config $configHelper,
        UrlFinderInterface $urlFinder,
        Serializer $serializer,
        array $skipRequestIdentifiers = []
    ) {
        parent::__construct($context);
        $this->settingCollectionFactory = $settingCollectionFactory;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->urlFinder = $urlFinder;
        $this->skipRequestIdentifiers = array_merge($this->skipRequestIdentifiers, $skipRequestIdentifiers);
        $this->serializer = $serializer;
    }

    public function getSeoSignificantAttributeCodes(): array
    {
        if ($this->seoSignificantAttributeCodes === null) {
            $filterCodes = [];

            if ($this->configHelper->isSeoUrlEnabled()) {
                $collection = $this->settingCollectionFactory->create();
                $yesValue = $this->configHelper->isGenerateSeoByDefault()
                    ? [GenerateSeoUrl::YES, GenerateSeoUrl::USE_DEFAULT]
                    : [GenerateSeoUrl::YES];
                $collection->addFieldToFilter(FilterSettingInterface::IS_SEO_SIGNIFICANT, $yesValue);
                $aliases = $collection->getColumnValues('attribute_url_alias');
                $filterCodes = $collection->getColumnValues(FilterSettingInterface::FILTER_CODE);
                array_walk($filterCodes, function (&$code) {
                    if (substr($code, 0, 5) == \Amasty\ShopbyBase\Helper\FilterSetting::ATTR_PREFIX) {
                        $code = substr($code, 5);
                    }
                });
                $this->setAttributeUrlAliases($filterCodes, $aliases);
            }

            $this->seoSignificantAttributeCodes = $filterCodes;
        }

        return $this->seoSignificantAttributeCodes;
    }

    private function setAttributeUrlAliases(array $filterCodes, array $aliases): void
    {
        foreach ($aliases as &$alias) {
            $alias = $this->serializer->unserialize($alias);
        }

        $this->attributeUrlAliases = array_combine($filterCodes, $aliases);
    }

    public function getAttributeUrlAliases(): array
    {
        return $this->attributeUrlAliases;
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function isAttributeSeoSignificant($attribute)
    {
        if ($attribute instanceof \Magento\Eav\Model\Entity\Attribute) {
            $attribute = $attribute->getAttributeCode();
        }
        $codes = $this->getSeoSignificantAttributeCodes();
        return in_array($attribute, $codes);
    }

    /**
     * @return string
     */
    public function getSpecialChar()
    {
        return $this->scopeConfig->getValue(self::AMASTY_SHOPBY_SEO_URL_SPECIAL_CHAR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getCanonicalRoot()
    {
        return $this->scopeConfig->getValue(self::CANONICAL_ROOT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getCanonicalCategory()
    {
        return $this->scopeConfig->getValue(self::CANONICAL_CATEGORY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getGeneralUrl()
    {
        return $this->scopeConfig->getValue(self::AMSHOPBY_ROOT_GENERAL_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isIncludeAttributeName()
    {
        return $this->scopeConfig->getValue(self::AMASTY_SHOPBY_SEO_URL_ATTRIBUTE_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getFilterWord()
    {
        return $this->scopeConfig->getValue(self::AMASTY_SHOPBY_SEO_URL_FILTER_WORD, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function isAddPageToMetaTitleEnabled()
    {
        return $this->scopeConfig->getValue(self::AMSHOPBY_SEO_PAGE_META_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function isEnableRelNofollow()
    {
        return $this->scopeConfig->getValue(self::AMSHOPBY_SEO_REL_NOFOLLOW, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function isAddPageToMetaDescriprionEnabled()
    {
        return $this->scopeConfig->getValue(self::AMSHOPBY_SEO_PAGE_META_DESCR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->_urlBuilder;
    }

    /**
     * @param RequestInterface $request
     * @param bool $allowEmptyModuleName = false
     * @return bool;
     */
    public function isAllowedRequest(RequestInterface $request, $allowEmptyModuleName = false)
    {
        if (!$allowEmptyModuleName && !$request->getModuleName()) {
            return false;
        }

        $identifier = ltrim($request->getOriginalPathInfo(), '/');
        if (!empty($identifier)) {
            $this->skipXsearchIdentifier();
            foreach ($this->skipRequestIdentifiers as $skipRequestIdentifier) {
                if (strpos($identifier, $skipRequestIdentifier) === 0) {
                    return false;
                }
            }

            $rewrite = $this->urlFinder->findOneByData([
                UrlRewrite::REQUEST_PATH => $identifier,
                UrlRewrite::STORE_ID => $this->storeManager->getStore()->getId(),
            ]);
            if ($rewrite !== null) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function skipXsearchIdentifier()
    {
        if ($this->configHelper->isModuleOutputEnabled('Amasty_Xsearch')
            && $this->configHelper->getConfig('amasty_xsearch/general/enable_seo_url')
        ) {
            $this->skipRequestIdentifiers[] = $this->configHelper->getConfig('amasty_xsearch/general/seo_key');
        }
    }

    /**
     * @return bool
     */
    public function isModuleEnabled()
    {
        return !!$this->scopeConfig->getValue(self::IS_MODULE_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
