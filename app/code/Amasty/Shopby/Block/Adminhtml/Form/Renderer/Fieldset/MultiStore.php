<?php

namespace Amasty\Shopby\Block\Adminhtml\Form\Renderer\Fieldset;

use Amasty\ShopbyBase\Model\FilterSetting\StoreSettingResolver;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Store\Model\Store;

class MultiStore extends Element
{
    /**
     * @var string
     */
    protected $_template = 'form/renderer/fieldset/multistore.phtml';

    /**
     * @var Factory
     */
    protected $elementFactory;

    /**
     * @var StoreSettingResolver
     */
    private $storeSettingResolver;

    public function __construct(
        Context $context,
        Factory $elementFactory,
        StoreSettingResolver $storeSettingResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory = $elementFactory;
        $this->storeSettingResolver = $storeSettingResolver;
    }

    /**
     * @param $storeId
     * @return null|string
     */
    public function getStoreValue($storeId)
    {
        if ($value = $this->getElement()->getValue()) {
            $value = $this->storeSettingResolver->chooseStoreLabel($value, $storeId);
        }

        return $value;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores()
    {
        $store = $this->_storeManager->getStores();
        ksort($store);

        return $store;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return bool
     */
    public function isDefaultStore(\Magento\Store\Api\Data\StoreInterface $store)
    {
        return $store->getStoreId() == Store::DEFAULT_STORE_ID;
    }
}
