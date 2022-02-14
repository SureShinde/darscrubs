<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

use Amasty\GroupedOptions\Model\GroupAttr\DataFactoryProviderInterface;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

class AddGroupOptions
{
    /**
     * @var DataFactoryProviderInterface
     */
    private $dataFactoryProvider;

    public function __construct(DataFactoryProviderInterface $dataFactoryProvider)
    {
        $this->dataFactoryProvider = $dataFactoryProvider;
    }

    public function afterGetSelectOptions(AbstractFrontend $subject, array $options): array
    {
        $dataProvider = $this->dataFactoryProvider->create();
        $groups = $dataProvider->getGroupsByAttributeId((int) $subject->getAttribute()->getAttributeId());
        if ($groups) {
            $groupOptions = [];
            $allGroupedOptions = [];
            foreach ($groups as $group) {
                $groupOptions[] = [
                    'label' => $group->getName(),
                    'value' => $group->getGroupCode()
                ];
                if ($group->hasOptions()) {
                    foreach ($group->getOptions() as $option) {
                        $allGroupedOptions[] = $option->getOptionId();
                    }
                }
            }

            if (count($allGroupedOptions)) {
                foreach ($options as $key => $value) {
                    if (in_array($value['value'], $allGroupedOptions)) {
                        unset($options[$key]);
                    }
                }
            }

            $options = array_merge($groupOptions, $options);
        }

        return $options;
    }
}
