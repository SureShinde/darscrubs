<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Ui\Component\Listing\Columns;

use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Store\Ui\Component\Listing\Column\Store;

class Scope extends Store
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        Escaper $escaper,
        ConfigProvider $configProvider,
        array $components = [],
        array $data = [],
        $storeKey = 'scope'
    ) {
        parent::__construct($context, $uiComponentFactory, $systemStore, $escaper, $components, $data, $storeKey);
        $this->configProvider = $configProvider;
    }

    /**
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        $attrCode = $item[BrandAttribute::ORIG_FILTER_CODE] ?? $item[FilterSettingInterface::FILTER_CODE] ?? null;
        if ($attrCode) {
            $allAttributeCodes = $this->configProvider->getAllBrandAttributeCodes();
            $storeIds = [];
            foreach ($allAttributeCodes as $storeId => $code) {
                if ($attrCode === $code || $attrCode === FilterSetting::ATTR_PREFIX . $code) {
                    $storeIds[] = $storeId;
                }
            }
            $item[$this->getData('name')] = $storeIds;
        }

        return parent::prepareItem($item);
    }
}
