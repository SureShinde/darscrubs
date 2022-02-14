<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\CatalogSearch\Model\Layer\Filter\Decimal;

use Amasty\GroupedOptions\Plugin\CatalogSearch\Model\Layer\Filter\ChangeDecimalLabels;
use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\CatalogSearch\Model\Layer\Filter\Decimal as DecimalFilter;

class ChangeLabel
{
    /**
     * @var ChangeDecimalLabels
     */
    private $changeDecimalLabels;

    public function __construct(ChangeDecimalLabels $changeDecimalLabels)
    {
        $this->changeDecimalLabels = $changeDecimalLabels;
    }

    /**
     * @param DecimalFilter $subject
     * @param FilterItem[] $items
     * @return FilterItem[]
     */
    public function afterGetItems(DecimalFilter $subject, array $items): array
    {
        return $this->changeDecimalLabels->execute(
            (int) $subject->getAttributeModel()->getAttributeId(),
            $items
        );
    }
}
