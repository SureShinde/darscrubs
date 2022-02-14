<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Store\Switcher;

use Amasty\ShopbyBase\Api\UrlBuilderInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\EncoderInterface;
use Magento\Store\Model\StoreManagerInterface;

class ModifySwitcherPostData
{
    const STORE_PARAM_NAME = '___store';

    /**
     * @var UrlBuilderInterface
     */
    private $urlBuilder;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var PostHelper
     */
    private $postHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        UrlBuilderInterface $urlBuilder,
        EncoderInterface $encoder,
        PostHelper $postHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->encoder = $encoder;
        $this->postHelper = $postHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Store\Block\Switcher $subject
     * @param \Closure $closure
     * @param $store
     * @param array $data
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetTargetStorePostData(
        \Magento\Store\Block\Switcher $subject,
        \Closure $closure,
        \Magento\Store\Model\Store $store,
        $data = []
    ) {
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = ['_' => null, 'shopbyAjax' => null, 'amshopby' => null];
        $params['_scope'] = $store;

        $currentUrl = $this->urlBuilder->getUrl('*/*/*', $params);
        $data[self::STORE_PARAM_NAME] = $store->getCode();
        $data['___from_store'] = $this->storeManager->getStore()->getCode();
        $data[ActionInterface::PARAM_NAME_URL_ENCODED] = $this->encoder->encode($currentUrl);

        $url = $subject->getUrl('stores/store/redirect');

        return $this->postHelper->getPostData($url, $data);
    }
}
