<?php

namespace Amasty\ShopbyBrand\Observer\Admin;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OptionFormFeatured implements ObserverInterface
{
    /**
     * @var Yesno
     */
    private $yesNoSource;

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    
    public function __construct(
        Yesno $yesNosource,
        ConfigProvider $configProvider
    ) {
        $this->yesNoSource = $yesNosource;
        $this->configProvider = $configProvider;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Form $fieldSet */
        $fieldSet = $observer->getEvent()->getFieldset();
        $setting = $observer->getEvent()->getSetting();
        $storeId = $observer->getEvent()->getStoreId();
        $brandFilterCode = FilterSetting::ATTR_PREFIX . $this->configProvider->getBrandAttributeCode((int) $storeId);

        if ($setting->getFilterCode() == $brandFilterCode) {
            $fieldSet->setData('legend', 'Brand Options');

            $fieldSet->addField(
                OptionSettingInterface::IS_SHOW_IN_WIDGET,
                'select',
                [
                    'name' => OptionSettingInterface::IS_SHOW_IN_WIDGET,
                    'label' => __('Show in Brand List Widget'),
                    'title' => __('Show in Brand List Widget'),
                    'values' => $this->yesNoSource->toOptionArray(),
                    'value' => 1
                ]
            );

            $fieldSet->addField(
                OptionSettingInterface::IS_SHOW_IN_SLIDER,
                'select',
                [
                    'name' => OptionSettingInterface::IS_SHOW_IN_SLIDER,
                    'label' => __('Show in Brand Slider Widget'),
                    'title' => __('Show in Brand Slider Widget'),
                    'values' => $this->yesNoSource->toOptionArray(),
                ]
            );

            $fieldSet->addField(
                'slider_position',
                'text',
                [
                    'name' => 'slider_position',
                    'label' => __('Position in Slider'),
                    'title' => __('Position in Slider')
                ]
            );
        }
    }
}
