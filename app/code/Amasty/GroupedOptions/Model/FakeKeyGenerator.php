<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model;

class FakeKeyGenerator
{
    const LAST_POSSIBLE_OPTION_ID = (2 << 30) - 1;
    
    public function generate(int $groupId): int
    {
        return self::LAST_POSSIBLE_OPTION_ID - $groupId;
    }
}
