<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search;

use Magento\Framework\Search\Response\QueryResponse;
use \Magento\Framework\Search\Response\Aggregation;

class EmptyResponseFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(): QueryResponse
    {
        $aggregations = $this->objectManager->create(
            Aggregation::class,
            ['buckets' => []]
        );

        return $this->objectManager->create(
            QueryResponse::class,
            [
                'documents' => [],
                'aggregations' => $aggregations,
                'total' => 0
            ]
        );
    }
}
