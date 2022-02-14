<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\UrlResolver;

interface UrlResolverInterface
{
    /**
     * Resolve an url
     *
     * @return string
     */
    public function resolve(): string;
}
