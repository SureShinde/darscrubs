<?php

namespace Amasty\Shopby\Helper;

use Magento\Framework\App\Helper\Context;
use Amasty\ShopbyBase\Api\UrlBuilderInterface;

class State extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var UrlBuilderInterface
     */
    private $amUrlBuilder;

    public function __construct(
        Context $context,
        UrlBuilderInterface $amUrlBuilder
    ) {
        parent::__construct($context);
        $this->amUrlBuilder = $amUrlBuilder;
    }

    /**
     * @return mixed
     */
    public function getCurrentUrl()
    {
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = ['_' => null, 'shopbyAjax' => null, 'dt' => null, 'df' => null];

        $result = str_replace('&amp;', '&', $this->amUrlBuilder->getUrl('*/*/*', $params));
        return $result;
    }
}
