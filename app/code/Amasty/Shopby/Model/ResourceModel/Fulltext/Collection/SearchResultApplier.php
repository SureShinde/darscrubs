<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Amasty\ShopbyBase\Model\Di\Wrapper;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\Framework\Search\EngineResolverInterface;

class SearchResultApplier
{
    /**
     * @var SearchResultApplierFactory
     */
    private $elasticSearchResultApplierFactory;

    /**
     * @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory
     */
    private $mysqlSearchResultApplierFactory;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    public function __construct(
        SearchResultApplierFactory $elasticSearchResultApplierFactory,
        EngineResolverInterface $engineResolver,
        Wrapper $mysqlSearchResultApplierFactory
    ) {
        $this->elasticSearchResultApplierFactory = $elasticSearchResultApplierFactory;
        $this->mysqlSearchResultApplierFactory = $mysqlSearchResultApplierFactory;
        $this->engineResolver = $engineResolver;
    }

    public function getApplier(array $data): SearchResultApplierInterface
    {
        if ($this->engineResolver->getCurrentSearchEngine() == Collection::MYSQL_ENGINE) {
            return $this->mysqlSearchResultApplierFactory->create($data);
        } else {
            return $this->elasticSearchResultApplierFactory->create($data);
        }
    }
}
