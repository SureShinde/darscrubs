<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\UrlRewrite\Model\StoreSwitcher\RewriteUrl;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\UrlRewrite\Model\StoreSwitcher\RewriteUrl;

class ModifySwitchUrl
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    public function beforeSwitch(
        RewriteUrl $subject,
        StoreInterface $fromStore,
        StoreInterface $targetStore,
        string $redirectUrl
    ): array {
        $this->request = $this->requestFactory->create();
        $this->request->setUri($redirectUrl);
        return [$fromStore, $targetStore, $redirectUrl];
    }

    public function afterSwitch(RewriteUrl $subject, string $result): string
    {
        $requestUri = $this->request->getUri()->toString();
        $queryString = $this->request->getUri()->getQuery();
        if ($requestUri != $result && $queryString) {
            $result .= '?' . $queryString;
        }

        return $result;
    }
}
