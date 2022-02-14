<?php

namespace Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter;

use Magento\Framework\Search\Request\BucketInterface as RequestBucketInterface;

interface BucketBuilderInterface
{
    /**
     * @param RequestBucketInterface $bucket
     * @param array $queryResult
     * @return array
     */
    public function build(
        RequestBucketInterface $bucket,
        array $queryResult
    );
}
