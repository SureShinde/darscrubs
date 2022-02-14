<?php

namespace Amasty\ShopbyBrand\Block\Catalog\Product\ProductList;

use Amasty\ShopbyBrand\Helper\Data;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Data\Helper\PostHelper;

class MoreFrom extends AbstractProduct
{
    const DEFAULT_PRODUCT_LIMIT = 7;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Stock
     */
    private $stockHelper;

    /**
     * Item collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection|array
     */
    private $itemCollection = [];

    /**
     * @var Status
     */
    private $productStatus;

    /**
     * @var Visibility
     */
    private $productVisibility;

    /**
     * @var PostHelper
     */
    private $postHelper;

    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Data $helper,
        Stock $stockHelper,
        Status $productStatus,
        Visibility $productVisibility,
        PostHelper $postHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helper = $helper;
        $this->stockHelper = $stockHelper;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->postHelper = $postHelper;
    }

    public function getItems(): array
    {
        $items = [];
        if (!$this->itemCollection) {
            $this->_prepareData();
        }

        if ($this->itemCollection) {
            $items = $this->itemCollection->getItems();
            shuffle($items);
        }

        return $items;
    }

    /**
     * @return $this
     */
    protected function _prepareData()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->_coreRegistry->registry('product');
        $attributeCode = $this->helper->getBrandAttributeCode();
        $attributeValue = $product->getData($attributeCode);

        if (!$attributeValue) {
            return $this;
        }
        $attributeValue = explode(',', $attributeValue);

        $this->initProductCollection(
            $attributeCode,
            $attributeValue,
            $product->getId()
        );

        return $this;
    }

    /**
     * @param string $attributeCode
     * @param array $attributeValue
     * @param int $currentProductId
     */
    private function initProductCollection($attributeCode, $attributeValue, $currentProductId)
    {
        $this->itemCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter($attributeCode, ['in' => $attributeValue])
            ->addFieldToFilter('entity_id', ['neq' => $currentProductId])
            ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds())
            ->addStoreFilter()
            ->setPageSize($this->getProductsLimit());

        $this->itemCollection->setCurPage(rand(1, $this->itemCollection->getLastPageNumber() - 1));
        $this->stockHelper->addInStockFilterToCollection($this->itemCollection);

        $this->itemCollection->load();

        foreach ($this->itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }
    }

    /**
     * @return int
     */
    private function getProductsLimit()
    {
        return $this->helper->getModuleConfig('more_from_brand/count') ? : self::DEFAULT_PRODUCT_LIMIT;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if ($this->isEnabled() && $this->getItems()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        return $this->helper->getModuleConfig('more_from_brand/enable');
    }

    /**
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getTitle()
    {
        $title = $this->helper->getModuleConfig('more_from_brand/title');
        preg_match_all('@\{(.+?)\}@', $title, $matches);
        if (isset($matches[1]) && !empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $value = '';
                switch ($match) {
                    case 'brand_name':
                        $value = $this->getBrandName();
                        break;
                }
                $title = str_replace('{' . $match . '}', $value, $title);
            }
        }

        $title = $title ?: __('More from this Brand');

        return $title;
    }

    /**
     * @return string
     */
    private function getBrandName()
    {
        $value = '';
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->_coreRegistry->registry('product');
        $attributeCode = $this->helper->getBrandAttributeCode();
        $attributeValue = $product->getData($attributeCode);
        $attribute = $product->getResource()->getAttribute($attributeCode);
        if ($attribute && $attribute->usesSource()) {
            $value = $attribute->getSource()->getOptionText($attributeValue);
        }

        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return $value;
    }

    /**
     * @return PostHelper
     */
    public function getPostHelper()
    {
        return $this->postHelper;
    }

    /**
     * @return \Magento\Catalog\Helper\Product\Compare
     */
    public function getCompareHelper()
    {
        return $this->_compareProduct;
    }

    /**
     * @return \Magento\Wishlist\Helper\Data
     */
    public function getWishlistHelper()
    {
        return $this->_wishlistHelper;
    }
}
