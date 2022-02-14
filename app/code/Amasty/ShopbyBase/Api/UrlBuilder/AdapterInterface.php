<?php

namespace Amasty\ShopbyBase\Api\UrlBuilder;

interface AdapterInterface
{
    /**
     * @param   string|null $routePath
     * @param   array|null $routeParams
     * @return  string
     */
    public function getUrl($routePath = null, $routeParams = null);
}
