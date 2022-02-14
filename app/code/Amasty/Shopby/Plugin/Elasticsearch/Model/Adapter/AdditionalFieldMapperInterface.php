<?php

namespace Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter;

/**
 * Interface AdditionalFieldMapperInterface
 * @package Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter
 */
interface AdditionalFieldMapperInterface
{
    /**
     * @return array
     */
    public function getAdditionalAttributeTypes();

    /**
     * @param array $context
     * @return string
     */
    public function getFiledName($context);
}
