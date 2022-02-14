<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Store\ViewModel\SwitcherUrlProvider;

use Amasty\ShopbyBase\Api\UrlBuilderInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Amasty\ShopbyBase\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class ModifyUrlData
{
    const STORE_PARAM_NAME = '___store';
    const FROM_STORE_PARAM_NAME = '___from_store';

    /**
     * @var UrlBuilderInterface
     */
    private $urlBuilder;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var Emulation
     */
    private $emulation;

    public function __construct(
        UrlBuilderInterface $urlBuilder,
        EncoderInterface $encoder,
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        DataPersistorInterface $dataPersistor
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->encoder = $encoder;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param Store $store
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetTargetStoreRedirectUrl($subject, callable $proceed, Store $store)
    {
        $this->emulation->startEnvironmentEmulation(
            $store->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_scope'] = $store;
        $params['_query'] = ['_' => null, 'shopbyAjax' => null, 'amshopby' => null];
        $this->dataPersistor->set(Data::SHOPBY_SWITCHER_STORE_ID, $store->getId());
        $currentUrl = $this->urlBuilder->getUrl('*/*/*', $params);
        $this->dataPersistor->clear(Data::SHOPBY_SWITCHER_STORE_ID);

        $this->emulation->stopEnvironmentEmulation();
        return $this->urlBuilder->getUrl(
            'stores/store/redirect',
            [
                self::STORE_PARAM_NAME => $store->getCode(),
                self::FROM_STORE_PARAM_NAME => $this->storeManager->getStore()->getCode(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->encoder->encode($currentUrl),
            ]
        );
    }
}
