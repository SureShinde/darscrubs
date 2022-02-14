<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

use Amasty\GroupedOptions\Model\GroupAttr\DataProvider;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

class RetrieveGroupOptionLabel
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
     * @param AbstractFrontend $subject
     * @param string|bool $optionText
     * @param int $optionId
     * @return string|bool
     */
    public function afterGetOption(AbstractFrontend $subject, $optionText, $optionId)
    {
        if (!$optionText && is_scalar($optionId)) {
            $group = $this->dataProvider->getGroupByAttributeId(
                (int) $subject->getAttribute()->getAttributeId(),
                (string) $optionId
            );
            if ($group) {
                $optionText = $group->getName();
            }
        }

        return $optionText;
    }
}
