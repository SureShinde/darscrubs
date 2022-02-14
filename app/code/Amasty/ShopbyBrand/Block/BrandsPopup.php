<?php

namespace Amasty\ShopbyBrand\Block;

use Amasty\ShopbyBase\Helper\Data;

class BrandsPopup extends \Amasty\ShopbyBrand\Block\Widget\BrandList
{
    const CONFIG_VALUES_PATH = 'amshopby_brand/general/brands_popup_config';

    /**
     * @var string
     */
    protected $_template = 'Amasty_ShopbyBrand::brands_popup.phtml';

    /**
     * @var bool
     */
    protected $shouldWrap = true;

    /**
     * @var bool
     */
    protected $portoTheme = false;

    /**
     * @var bool
     */
    protected $ultimoTheme = false;

    /**
     * @return string
     */
    public function getOnlyContent()
    {
        $this->shouldWrap = false;
        return $this->toHtml();
    }

    /**
     * @return bool
     */
    public function isShowPopup()
    {
        return (bool)$this->helper->getModuleConfig('general/brands_popup');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helper->getBrandLabel();
    }

    /**
     * @return string
     */
    public function getAllBrandsUrl()
    {
        return $this->helper->getAllBrandsUrl();
    }

    /**
     * @return array
     */
    public function getIndex()
    {
        $this->getDataPersistor()->set(Data::SHOPBY_BRAND_POPUP, true);
        $items = parent::getIndex();
        $this->getDataPersistor()->clear(Data::SHOPBY_BRAND_POPUP);

        return $items;
    }

    /**
     * @return bool
     */
    public function isAllBrandsPage()
    {
        $path = $this->getRequest()->getOriginalPathInfo();
        if ($path && $path !== '/') {
            $isAllBrandsPage = strpos(
                $this->helper->getAllBrandsUrl(),
                $path
            ) !== false;
        } else {
            $isAllBrandsPage = false;
        }

        return $isAllBrandsPage;
    }

    /**
     * @return bool
     */
    public function isShouldWrap()
    {
        return $this->shouldWrap;
    }

    public function setPortoTheme()
    {
        $this->portoTheme = true;
    }

    /**
     * @return bool
     */
    public function isPortoTheme()
    {
        return $this->portoTheme;
    }

    public function setUltimoTheme()
    {
        $this->ultimoTheme = true;
    }

    /**
     * @return bool
     */
    public function isUltimoTheme()
    {
        return $this->ultimoTheme;
    }

    protected function getConfigValuesPath(): string
    {
        return self::CONFIG_VALUES_PATH;
    }
}
