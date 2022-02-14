<?php

namespace Amasty\ShopbyBrand\Plugin\Xsearch\Block\Search;

use Amasty\ShopbyBrand\Model\Brand\ListDataProvider\LoadItems;
use Magento\Store\Model\StoreManagerInterface;

class Brand
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoadItems
     */
    private $loadItems;

    public function __construct(
        StoreManagerInterface $storeManager,
        LoadItems $loadItems
    ) {
        $this->storeManager = $storeManager;
        $this->loadItems = $loadItems;
    }

    /**
     * @param \Amasty\Xsearch\Block\Search\Brand $subject
     * @param array $result
     * @return array
     */
    public function afterGetBrands($subject, array $result)
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return array_merge($result, $this->loadItems->getData($storeId));
    }
}
