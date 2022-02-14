<?php
namespace Amasty\Shopby\Api\Data;

interface FromToFilterInterface
{
    /**
     * @return string[]
     */
    public function getFromToConfig(): array;
}
