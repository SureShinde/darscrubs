<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\Source\GroupGrid;

use Amasty\GroupedOptions\Model\Product\Attribute\BuildOptionsArray;
use Amasty\GroupedOptions\Model\Source\GroupForm\AttributeOption as FormAttributeOption;
use Magento\Framework\Data\OptionSourceInterface;

class AttributeOption implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var FormAttributeOption
     */
    protected $option;

    public function __construct(FormAttributeOption $option)
    {
        $this->option = $option;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $data = $this->option->toOptionArray();
            $options = [];
            foreach ($data as $code => $value) {
                $scope = $value['options'];
                foreach ($scope as &$item) {
                    $item['code'] = $code;
                    if ($item['type'] == BuildOptionsArray::SWATCH_IMAGE) {
                        $item['swatch'] = 'background-image:url(' . $item['swatch'] . ');background-size:cover';
                    } elseif ($item['type'] == BuildOptionsArray::SWATCH) {
                        $item['swatch'] = 'background:' . $item['swatch'];
                    }
                }

                // @codingStandardsIgnoreLine
                $options = array_merge($options, $scope);
            }
            $this->options = $options;
        }

        return $this->options;
    }
}
