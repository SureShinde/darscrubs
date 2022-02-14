<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\Button\Group;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

abstract class GenericButton implements ButtonProviderInterface
{
    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    public function getGroupId(): int
    {
        return (int) $this->request->getParam('group_id');
    }

    public function getUrl(string $route, array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
