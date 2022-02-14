<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\UrlResolver;

class ClearUrlResolver implements UrlResolverInterface
{
    /**
     * @var \Amasty\Shopby\Helper\State
     */
    private $layer;

    /**
     * @var \Amasty\ShopbyBase\Api\UrlBuilderInterface
     */
    private $amUrlBuilder;

    public function __construct(
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Amasty\ShopbyBase\Api\UrlBuilderInterface $amUrlBuilder
    ) {
        $this->layer = $layerResolver->get();
        $this->amUrlBuilder = $amUrlBuilder;
    }

    /**
     * @return array
     */
    private function getActiveFilters(): array
    {
        $filters = $this->layer->getState()->getFilters();
        if (!is_array($filters)) {
            $filters = [];
        }
        return $filters;
    }

    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function resolve(): string
    {
        $filterState = ['_' => null, 'shopbyAjax' => null, 'p' => null];
        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getFilter()->getRequestVar()] = $item->getFilter()->getCleanValue();
        }

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->amUrlBuilder->getUrl('*/*/*', $params);
    }
}
