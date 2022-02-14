<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Shopby\Helper\Content;

use Amasty\GroupedOptions\Model\GroupAttr\DataProvider;
use Amasty\Shopby\Helper\Content;
use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class AddGroupOptionData
{
    /**
     * @var DataProvider
     */
    private $dataProvider;

    public function __construct(DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function afterGetOption(
        Content $subject,
        OptionSettingInterface $result,
        string $value,
        string $filterCode,
        Attribute $attrModel
    ): OptionSettingInterface {
        if (!$result->getLabel()) {
            $group = $this->dataProvider->getGroupByAttributeId((int) $attrModel->getAttributeId(), $value);
            if ($group) {
                $label = $group->getName();
                if ($label) {
                    $result->setTitle($label);
                    $result->setMetaTitle($label);
                    $result->setValue($value);
                }
            }
        }

        return $result;
    }
}
