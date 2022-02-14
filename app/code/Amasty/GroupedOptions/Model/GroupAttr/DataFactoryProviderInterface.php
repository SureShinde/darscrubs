<?php

namespace Amasty\GroupedOptions\Model\GroupAttr;

interface DataFactoryProviderInterface
{
    public function create(array $data = []): DataProvider;
}
