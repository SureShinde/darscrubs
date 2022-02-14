<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolver as MysqlTotalRecordsResolver;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolver as ElasticTotalRecordsResolver;
use Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolverFactory
    as ElasticTotalRecordsResolverFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolverFactory
    as MysqlTotalRecordsResolverFactory;
use Magento\Framework\Search\EngineResolverInterface;

class TotalRecordsResolver
{
    /**
     * @var ElasticTotalRecordsResolverFactory
     */
    private $elasticResolverFactory;

    /**
     * @var MysqlTotalRecordsResolverFactory
     */
    private $mysqlResolverFactory;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    public function __construct(
        ElasticTotalRecordsResolverFactory $elasticResolverFactory,
        MysqlTotalRecordsResolverFactory $mysqlResolverFactory,
        EngineResolverInterface $engineResolver
    ) {
        $this->elasticResolverFactory = $elasticResolverFactory;
        $this->mysqlResolverFactory = $mysqlResolverFactory;
        $this->engineResolver = $engineResolver;
    }

    /**
     * @param array $data
     * @return MysqlTotalRecordsResolver|ElasticTotalRecordsResolver|
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
