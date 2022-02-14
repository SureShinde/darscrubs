<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver as MysqlSearchCriteriaResolver;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolverFactory
    as MysqlSearchCriteriaResolverFactory;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolver
    as ElasticSearchCriteriaResolver;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchCriteriaResolverFactory
    as ElasticSearchCriteriaResolverFactory;
use Magento\Framework\Search\EngineResolverInterface;

class SearchCriteriaResolver
{
    /**
     * @var ElasticSearchCriteriaResolverFactory
     */
    private $elasticResolverFactory;

    /**
     * @var MysqlSearchCriteriaResolverFactory
     */
    private $mysqlResolverFactory;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    public function __construct(
        ElasticSearchCriteriaResolverFactory $elasticResolverFactory,
        MysqlSearchCriteriaResolverFactory $mysqlResolverFactory,
        EngineResolverInterface $engineResolver
    ) {
        $this->elasticResolverFactory = $elasticResolverFactory;
        $this->mysqlResolverFactory = $mysqlResolverFactory;
        $this->engineResolver = $engineResolver;
    }

    /**
     * @param array $data
     * @return MysqlSearchCriteriaResolver|ElasticSearchCriteriaResolver
     */
    public function getResolver(array $data)
    {
        if ($this->engineResolver->getCurrentSearchEngine() == Collection::MYSQL_ENGINE) {
            return $this->mysqlResolverFactory->create($data);
        } else {
            return $this->elasticResolverFactory->create($data);
        }
    }
}
