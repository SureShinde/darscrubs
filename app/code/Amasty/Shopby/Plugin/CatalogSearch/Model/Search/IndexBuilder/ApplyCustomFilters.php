<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\CatalogSearch\Model\Search\IndexBuilder;

use Amasty\Shopby\Model\Layer\Cms\Manager as CmsManager;
use Amasty\Shopby\Model\ConfigProvider;
use Amasty\Shopby\Model\Request;
use Amasty\Shopby\Model\ResourceModel\Search\FilterMapper\CustomExclusionStrategyPool;
use Amasty\Shopby\Model\ResourceModel\Search\IndexBuilder as IndexBuilderResource;
use Amasty\Shopby\Model\Source\StockFilterSource;
use Amasty\Shopby\Plugin\CatalogSearch\Model\Search\FilterMapper\CustomExclusionStrategy;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\Filter\BoolExpression;
use Magento\Framework\Search\Request\Query\Filter;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\DB\Select;

class ApplyCustomFilters
{
    /**
     * @var Request
     */
    private $shopbyRequest;

    /**
     * @var CmsManager
     */
    private $cmsManager;

    /**
     * @var CustomExclusionStrategyPool
     */
    private $customExclusionStrategyPool;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Configuration
     */
    private $searchConfiguration;

    /**
     * @var IndexBuilderResource
     */
    private $indexBuilderResource;

    public function __construct(
        Request $shopbyRequest,
        CmsManager $cmsManager,
        CustomExclusionStrategyPool $customExclusionStrategyPool,
        IndexBuilderResource $indexBuilderResource,
        ConfigProvider $configProvider,
        Configuration $searchConfiguration
    ) {
        $this->shopbyRequest = $shopbyRequest;
        $this->cmsManager = $cmsManager;
        $this->customExclusionStrategyPool = $customExclusionStrategyPool;
        $this->indexBuilderResource = $indexBuilderResource;
        $this->configProvider = $configProvider;
        $this->searchConfiguration = $searchConfiguration;
    }

    /**
     * Build index query
     *
     * @param $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function aroundBuild($subject, callable $proceed, RequestInterface $request)
    {
        $select = $proceed($request);
        $filters = $this->getFilters($request->getQuery());
        $isStock = false;
        foreach ($filters as $filter) {
            $this->customExclusionStrategyPool->applyFilter($filter, $select);
            if ($filter->getField() == StockFilterSource::STOCK_STATUS) {
                $isStock = true;
            }
        }

        if ($this->searchConfiguration->isShowOutOfStock() && $this->configProvider->isStockFilterEnabled()
            && ($this->shopbyRequest->getParam('stock') || $isStock)
        ) {
            $this->indexBuilderResource->addStockDataToSelect($select);
        }

        if ($this->cmsManager->isCmsPageNavigation()) {
            $this->cmsManager->addCmsPageDataToSelect($select);
        }

        return $select;
    }

    private function getFilters(RequestQueryInterface $query): array
    {
        $filters = [];
        switch ($query->getType()) {
            case RequestQueryInterface::TYPE_BOOL:
                /** @var \Magento\Framework\Search\Request\Query\BoolExpression $query */
                $this->resolveQueryFilters($query->getMust(), $filters);
                $this->resolveQueryFilters($query->getShould(), $filters);
                $this->resolveQueryFilters($query->getMustNot(), $filters);
                break;
            case RequestQueryInterface::TYPE_FILTER:
                /** @var Filter $query */
                $filter = $query->getReference();
                if (FilterInterface::TYPE_BOOL === $filter->getType()) {
                    $filters = array_merge($filters, $this->getFiltersFromBoolFilter($filter));
                } else {
                    $filters[] = $filter;
                }
                break;
            default:
                break;
        }

        return $filters;
    }

    private function resolveQueryFilters(array $subQueries, array &$filters): void
    {
        $filtersArray = [];
        foreach ($subQueries as $subQuery) {
            $filtersArray[] = $this->getFilters($subQuery);
        }

        $filters = array_merge($filters, ...$filtersArray);
    }

    /**
     * @param BoolExpression $boolExpression
     * @return FilterInterface[]
     */
    private function getFiltersFromBoolFilter(BoolExpression $boolExpression): array
    {
        $filters = [];

        /** @var BoolExpression $filter */
        $this->prepareFilters($boolExpression->getMust(), $filters);
        $this->prepareFilters($boolExpression->getShould(), $filters);
        $this->prepareFilters($boolExpression->getMustNot(), $filters);

        return $filters;
    }

    private function prepareFilters(array $filterScope, array &$filters): void
    {
        $filtersArray = [];
        foreach ($filterScope as $filter) {
            if ($filter->getType() === FilterInterface::TYPE_BOOL) {
                $filtersArray[] = $this->getFiltersFromBoolFilter($filter);
            } else {
                $filters[] = $filter;
            }
        }

        $filters = array_merge($filters, ...$filtersArray);
    }
}
