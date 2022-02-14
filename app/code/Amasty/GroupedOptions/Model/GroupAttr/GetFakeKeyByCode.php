<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\GroupAttr;

use Amasty\GroupedOptions\Model\FakeKeyGenerator;

class GetFakeKeyByCode
{
    /**
     * @var DataFactoryProviderInterface
     */
    private $dataFactoryProvider;

    /**
     * @var FakeKeyGenerator
     */
    private $fakeKeyGenerator;

    public function __construct(
        DataFactoryProviderInterface $dataFactoryProvider,
        FakeKeyGenerator $fakeKeyGenerator
    ) {
        $this->dataFactoryProvider = $dataFactoryProvider;
        $this->fakeKeyGenerator = $fakeKeyGenerator;
    }

    public function execute(int $attributeId, string $groupCode): ?int
    {
        $dataProvider = $this->dataFactoryProvider->create();
        $groups = $dataProvider->getGroupsByAttributeId($attributeId);
        foreach ($groups as $group) {
            if ($group->getGroupCode() == $groupCode) {
                $optionFromGroup = $this->fakeKeyGenerator->generate((int) $group->getId());
                break;
            }
        }

        return $optionFromGroup ?? null;
    }
}
