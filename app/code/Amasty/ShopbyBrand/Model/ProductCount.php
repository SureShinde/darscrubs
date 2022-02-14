<?php

namespace Amasty\ShopbyBrand\Model;

class ProductCount
{
    /**
     * @var null|array
     */
    private $productCount = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Amasty\ShopbyBrand\Helper\Data
     */
    private $brandHelper;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Amasty\ShopbyBrand\Helper\Data $brandHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->messageManager = $messageManager;
        $this->brandHelper = $brandHelper;
    }

    /**
     * Get brand product count
     *
     * @param int $optionId
     * @return int
     */
    public function get($optionId)
    {
        if ($this->productCount === null) {
            $attrCode = $this->brandHelper->getBrandAttributeCode();

            try {
                $this->productCount = $this->loadProductCount($attrCode);
            } catch (\Magento\Framework\Exception\StateException $e) {
                if (!$this->messageManager->hasMessages()) {
                    $this->messageManager->addErrorMessage(
                        __('Make sure that the root category for current store is anchored')
                    )->addErrorMessage(
                        __('Make sure that "%1" attribute can be used in layered navigation', $attrCode)
                    );
                }
                $this->productCount = [];
            }

        }

        return isset($this->productCount[$optionId]) ? $this->productCount[$optionId]['count'] : 0;
    }

    /**
     * @param string $attrCode
     *
     * @return array
     */
    private function loadProductCount($attrCode)
    {
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $category = $this->categoryRepository->get($rootCategoryId);
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection**/
        $collection = $this->collectionFactory->create();

        return $collection->addAttributeToSelect($attrCode)
            ->setVisibility([2,4])
            ->addCategoryFilter($category)
            ->getFacetedData($attrCode);
    }
}
