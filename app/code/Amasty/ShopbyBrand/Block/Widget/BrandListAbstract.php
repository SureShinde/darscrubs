<?php

namespace Amasty\ShopbyBrand\Block\Widget;

use Amasty\ShopbyBase\Api\UrlBuilderInterface;
use Amasty\ShopbyBrand\Helper\Data as DataHelper;
use Amasty\ShopbyBrand\Model\Brand\BrandListDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Eav\Model\Entity\Attribute\Option;

abstract class BrandListAbstract extends \Magento\Framework\View\Element\Template
{
    const PATH_BRAND_ATTRIBUTE_CODE = 'amshopby_brand/general/attribute_code';

    /**
     * @var DataHelper
     */
    protected $helper;

    /**
     * @var UrlBuilderInterface
     */
    private $amUrlBuilder;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var BrandListDataProvider
     */
    protected $brandListDataProvider;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        DataHelper $helper,
        UrlBuilderInterface $amUrlBuilder,
        BrandListDataProvider $brandListDataProvider,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->amUrlBuilder = $amUrlBuilder;
        $this->dataPersistor = $dataPersistor;
        $this->brandListDataProvider = $brandListDataProvider;

        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\Option $option
     * @return string
     */
    public function getBrandUrl(Option $option)
    {
        return $this->amUrlBuilder->getUrl('ambrand/index/index', ['id' => $option->getValue()]);
    }

    /**
     * @return DataPersistorInterface
     */
    public function getDataPersistor(): DataPersistorInterface
    {
        return $this->dataPersistor;
    }

    protected function _beforeToHtml()
    {
        $this->initializeBlockConfiguration();

        return parent::_beforeToHtml();
    }

    /**
     * deprecated. used for back compatibility.
     */
    public function initializeBlockConfiguration(): void
    {
        $configValues = $this->_scopeConfig->getValue(
            $this->getConfigValuesPath(),
            ScopeInterface::SCOPE_STORE
        );
        foreach (($configValues ?: []) as $option => $value) {
            if ($this->getData($option) === null) {
                $this->setData($option, $value);
            }
        }
    }

    abstract protected function getConfigValuesPath(): string;

    public function isDisplayZero(): bool
    {
        return (bool) $this->getData('display_zero');
    }
}
