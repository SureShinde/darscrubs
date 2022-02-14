<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterSettingResolver;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterSettingResolver as DecimalFilterSettingResolver;
use Amasty\Shopby\Model\Layer\Filter\Resolver\Decimal\FilterRequestDataResolver as DecimalFilterRequestDataResolover;

class FilterConfigResolver
{
    /**
     * @var FilterSettingResolver
     */
    private $settingResolver;

    /**
     * @var DecimalFilterSettingResolver
     */
    private $decimalSettingResolver;

    /**
     * @var DecimalFilterRequestDataResolover
     */
    private $decimalFilterRequestDataResolver;

    public function __construct(
        FilterSettingResolver $settingResolver,
        DecimalFilterSettingResolver $decimalSettingResolver,
        DecimalFilterRequestDataResolover $decimalFilterRequestDataResolver
    ) {
        $this->settingResolver = $settingResolver;
        $this->decimalSettingResolver = $decimalSettingResolver;
        $this->decimalFilterRequestDataResolver = $decimalFilterRequestDataResolver;
    }

    public function getConfig(FilterInterface $filter, array $facetedData): array
    {
        $config = [
            'from' => null,
            'to' => null,
            'min' => null,
            'max' => null,
            'deltaFrom' => null,
            'deltaTo' => null,
            'requestVar' => null,
            'step' => null,
            'template' => null,
            'currencySymbol' => null,
            'curRate' => 1,
        ];

        if ($this->validateFacetedData($facetedData)
                && $this->decimalSettingResolver->getUseSliderOrFromTo($filter)
        ) {
            $filterSetting = $this->settingResolver->getFilterSetting($filter);
            $min = $this->getMin((float) $facetedData['data']['min'], $filterSetting->getSliderMin());
            $max = $this->getMax($min, (float) $facetedData['data']['max'], $filterSetting->getSliderMax());

            $from = $this->decimalFilterRequestDataResolver->getCurrentFrom($filter) ?: '';
            $to = $this->decimalFilterRequestDataResolver->getCurrentTo($filter) ?: '';
            $template = $this->decimalSettingResolver->getSliderTemplate($filter);
            $currencySymbol = $this->decimalSettingResolver->getCurrencySymbol($filter);
            $currencyPosition = $this->decimalSettingResolver->getCurrencyPosition($filter);

            $config =
                [
                    'from' => $from,
                    'to' => $to,
                    'deltaFrom' => $this->decimalFilterRequestDataResolver->getDelta($filter),
                    'deltaTo' => $this->decimalFilterRequestDataResolver->getDelta($filter, false),
                    'min' => $from && $min > $from ? round($from) : $min,
                    'max' => $max < $to ? round($to) : $max,
                    'requestVar' => $filter->getRequestVar(),
                    'step' => round($filterSetting->getSliderStep(), 2),
                    'template' => $template,
                    'currencySymbol' => $currencySymbol,
                    'currencyPosition' => $currencyPosition,
                    'curRate' => $this->decimalSettingResolver->getCurrencyRate($filter)
                ];
        }

        return $config;
    }

    /**
     * @param $min
     * @param $sliderMin
     * @return mixed
     */
    private function getMin(float $min, float $sliderMin): float
    {
        if ($sliderMin) {
            $min = ($sliderMin < $min) ? $min : $sliderMin;
        }

        return $min;
    }

    /**
     * @param $min
     * @param $max
     * @param $sliderMax
     * @return mixed
     */
    private function getMax(float $min, float $max, float $sliderMax): float
    {
        if ($sliderMax) {
            $max = ($sliderMax > $max) && ($max > $min) ? $max : $sliderMax;
        }

        return $max;
    }

    /**
     * @param array $facetedData
     * @return bool
     */
    private function validateFacetedData(array $facetedData): bool
    {
        return isset($facetedData['data']['count']) && $facetedData['data']['count'] > 0;
    }
}
