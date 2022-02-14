<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Plugin\Shopby\Block\Navigation\SwatchesChoose;

use Amasty\GroupedOptions\Api\GroupRepositoryInterface;
use Amasty\Shopby\Block\Navigation\SwatchesChoose;

class ValidateGroupOptions
{
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    public function __construct(GroupRepositoryInterface $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function afterValidateValues(SwatchesChoose $subject, array $result): array
    {
        foreach ($result as $key => $value) {
            $group = $this->groupRepository->getGroupOptionsIds($value);

            if ($group) {
                unset($result[array_search($value, $result)]);
                // @codingStandardsIgnoreLine
                $result = array_merge($result, $group);
            }
        }
        
        return $result;
    }
}
