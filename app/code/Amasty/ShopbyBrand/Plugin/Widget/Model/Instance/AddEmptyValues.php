<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Plugin\Widget\Model\Instance;

use Magento\Widget\Model\Widget\Instance;

class AddEmptyValues
{
    public function afterGenerateLayoutUpdateXml(Instance $subject, string $xml): string
    {
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $types = ['Amasty\ShopbyBrand\Block\Widget\BrandSlider', 'Amasty\ShopbyBrand\Block\Widget\BrandList'];
        if ($xml && in_array($subject->getType(), $types)) {
            $emptyXml = '';
            foreach ($subject->getWidgetParameters() as $name => $parameter) {
                if ($parameter !== "") {
                    continue;
                }

                $emptyXml .= sprintf(
                    '<action method="setData"><argument name="name" xsi:type="string">'
                        . '%s</argument><argument name="value" xsi:type="string"></argument></action>',
                    $name
                );
            }

            $xml = str_replace('</block>', $emptyXml . '</block>', $xml);
        }

        return $xml;
    }
}
