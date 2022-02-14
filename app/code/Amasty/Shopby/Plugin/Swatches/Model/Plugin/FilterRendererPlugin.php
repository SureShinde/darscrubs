<?php

namespace Amasty\Shopby\Plugin\Swatches\Model\Plugin;

use Amasty\Shopby\Model\Source\DisplayMode;
use Amasty\Base\Model\Serializer;
use Magento\Swatches\Model\Swatch;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;

class FilterRendererPlugin
{
    /**
     * @var \Amasty\Shopby\Helper\FilterSetting
     */
    private $filterSetting;

    /**
     *
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        \Amasty\Shopby\Helper\FilterSetting $filterSetting,
        Serializer $serializer
    ) {
        $this->filterSetting = $filterSetting;
        $this->serializer = $serializer;
    }

    /**
     * @param $subject
     * @param FilterRenderer $filterRenderer
     * @param $closure
     * @param FilterInterface $filter
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeAroundRender($subject, FilterRenderer $filterRenderer, $closure, FilterInterface $filter)
    {
        if ($filter->hasAttributeModel()) {
            $displayMode = $this->getPreselectDisplayMode($filter);
            if (in_array($displayMode, [DisplayMode::MODE_DEFAULT, DisplayMode::MODE_DROPDOWN])) {
                $filter->getAttributeModel()->setData(
                    Swatch::SWATCH_INPUT_TYPE_KEY,
                    Swatch::SWATCH_INPUT_TYPE_DROPDOWN
                );
            }
        }

        return [$filterRenderer, $closure, $filter];
    }

    /**
     * @param FilterInterface $filter
     * @return int
     */
    private function getPreselectDisplayMode(FilterInterface $filter): int
    {
        $preselectValue = (int)$this->filterSetting->getSettingByLayerFilter($filter)->getDisplayMode();
        if ($preselectValue) {
            return $preselectValue;
        }

        return (int)$this->getDisplayModeFromAdditionalData($filter);
    }

    /**
     * @param FilterInterface $filter
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getDisplayModeFromAdditionalData(FilterInterface $filter): int
    {
        $preselectValue = 0;
        $additionalData = $filter->getAttributeModel()->getAdditionalData();
        $isDisplayModeSelect = $filter->getAttributeModel()->getFrontendInput() === DisplayMode::SELECT;
        if ($isDisplayModeSelect && $additionalData) {
            $additionalData = $this->serializer->unserialize($additionalData);
            $frontendInput = $additionalData[Swatch::SWATCH_INPUT_TYPE_KEY] ?? '';
            $preselectValue = DisplayMode::DISPLAY_MODE_FRONTEND_INPUT_MAP[$frontendInput] ?? 0;
        }

        return (int)$preselectValue;
    }
}
