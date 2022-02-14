<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Shopby\Block\Navigation\State;

use Amasty\GroupedOptions\Model\GroupAttr\DataProvider;
use Amasty\Shopby\Block\Navigation\State\Swatch;
use Magento\Framework\Exception\NoSuchEntityException;

class AddGroupedSwatches
{
    /**
     * @var DataProvider
     */
    private $dataProvider;

    public function __construct(DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param Swatch $subject
     * @param array $result
     * @param array $optionIds
     *
     * @return array
     */
    public function afterGetSwatches(Swatch $subject, array $result, array $optionIds): array
    {
        $groupedSwatches = $this->getSwatchesByOptions($optionIds, $result);
        if ($groupedSwatches) {
            $result += $groupedSwatches;
        }

        return $result;
    }

    private function getSwatchesByOptions(array $optionIds, array $result): array
    {
        $swatches = [];
        foreach ($optionIds as $optionId) {
            if (!array_key_exists($optionId, $result)) {
                try {
                    $group = $this->dataProvider->getByCode((string)$optionId);
                    $swatches[$group->getGroupCode()] = [
                        'option_id' => $group->getId(),
                        'type' => $group->getType(),
                        'value' => $group->getVisual() ?: $group->getName()
                    ];
                } catch (NoSuchEntityException $ex) {
                    continue;
                }
            }
        }

        return $swatches;
    }
}
