<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Elasticsearch\SearchAdapter\Filter\Builder;

use Magento\Elasticsearch\SearchAdapter\Filter\Builder\Range;
use Magento\Framework\Search\Request\FilterInterface as RequestFilterInterface;

class RangePlugin
{
    /**
     * @var \Magento\Elasticsearch\Model\Adapter\FieldMapperInterface
     */
    private $fieldMapper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $oManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->oManager = $objectManager;
    }

    public function aroundBuildFilter(Range $subject, callable $proceed, RequestFilterInterface $filter): array
    {
        if (!$filter->getFrom() && $this->getFieldMapper()) {
            $filterQuery = [];
            $fieldName = $this->getFieldMapper()->getFieldName($filter->getField());
            if ($filter->getFrom() !== null) {
                $filterQuery['range'][$fieldName]['gte'] = $filter->getFrom();
            }
            if ($filter->getTo()) {
                $filterQuery['range'][$fieldName]['lte'] = $filter->getTo();
            }

            $result = [$filterQuery];
        } else {
            $result = $proceed($filter);
        }

        return $result;
    }

    /**
     * deprecated; compatibility with magento 2.2.11
     * @return \Magento\Elasticsearch\Model\Adapter\FieldMapperInterface
     */
    public function getFieldMapper()
    {
        if (!$this->fieldMapper
            && class_exists(\Magento\Elasticsearch\Model\Adapter\FieldMapper\FieldMapperResolver::class)
        ) {
            $this->fieldMapper =
                $this->oManager->create(\Magento\Elasticsearch\Model\Adapter\FieldMapper\FieldMapperResolver::class);
        }

        return $this->fieldMapper;
    }
}
