<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
* @package Amasty_Base
*/


declare(strict_types=1);

namespace Amasty\Base\Model\SysInfo;

interface FormatterInterface
{
    public function format(array $info): array;
}
