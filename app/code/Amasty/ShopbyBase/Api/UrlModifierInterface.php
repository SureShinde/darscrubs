<?php

namespace Amasty\ShopbyBase\Api;

interface UrlModifierInterface
{
    /**
     * @param string $url
     * @return string
     */
    public function modifyUrl($url);
}
