<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\Product\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Store\Model\Store;
use Magento\Swatches\Helper\Media as SwatchHelper;
use Magento\Swatches\Model\Swatch;
use Magento\Swatches\Model\SwatchFactory;

class BuildOptionsArray
{
    const SWATCH = 1;
    const SWATCH_IMAGE = 2;
    const STORE_ID_FIELD = 'store_id';

    /**
     * @var null|array
     */
    private $swatchesByOptionId;

    /**
     * @var SwatchFactory
     */
    private $swatchFactory;

    /**
     * @var SwatchHelper
     */
    private $swatchHelper;

    public function __construct(
        SwatchFactory $swatchFactory,
        SwatchHelper $swatchHelper
    ) {
        $this->swatchFactory = $swatchFactory;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * @param Attribute[] $attributes
     * @return array
     */
    public function execute(array $attributes): array
    {
        $resultOptions = [];
        foreach ($attributes as $attribute) {
            $options = [];
            try {
                foreach ($attribute->getOptions() as $option) {
                    $scope = [
                        'value' => $option->getValue(),
                        'label' => $option->getLabel(),
                        $this->getSwatches($option->getValue())
                    ];
                    // @codingStandardsIgnoreLine
                    $options[] = array_merge(
                        $scope,
                        $this->getSwatches($option->getValue())
                    );
                }

                $resultOptions[$attribute->getAttributeId()] = [
                    'options' => $options,
                    'type' => $attribute->getFrontendInput()
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $resultOptions;
    }

    /**
     * @param $optionId
     * @return mixed
     */
    public function getSwatches($optionId)
    {
        $data = ['type' => 0, 'swatch' => '', 'id' => $optionId];
        if ($item = $this->getSwatchByOptionId($optionId)) {
            $data['type'] = $item->getType();
            if ($item->getType() == self::SWATCH_IMAGE) {
                $data['swatch'] = $this->swatchHelper->getSwatchAttributeImage('swatch_image', $item->getValue());
            } else {
                $data['swatch'] = $item->getValue();
            }
        }

        return $data;
    }

    private function getSwatchByOptionId($optionId): ?Swatch
    {
        if ($this->swatchesByOptionId === null) {
            $this->swatchesByOptionId = [];
            $collection = $this->swatchFactory->create()->getCollection()
                ->addFieldToFilter(self::STORE_ID_FIELD, Store::DEFAULT_STORE_ID);
            foreach ($collection as $item) {
                $this->swatchesByOptionId[$item->getOptionId()] = $item;
            }
        }

        return isset($this->swatchesByOptionId[$optionId]) ? $this->swatchesByOptionId[$optionId] : null;
    }
}
