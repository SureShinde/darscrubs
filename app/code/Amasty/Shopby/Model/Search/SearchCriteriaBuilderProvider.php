<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Search;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;

class SearchCriteriaBuilderProvider
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var array
     */
    private $filters = [];

    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param string $field
     * @param string|array $value
     * @return $this
     */
    public function addFilter(string $field, $value): SearchCriteriaBuilderProvider
    {
        $this->filters[$field] = $value;
        return $this;
    }

    public function removeFilter(string $field): void
    {
        unset($this->filters[$field]);
    }

    public function create(): \Magento\Framework\Api\Search\SearchCriteriaBuilder
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        foreach ($this->filters as $field => $value) {
            $this->filterBuilder->setField($field);
            $this->filterBuilder->setValue($value);
            $searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        return $searchCriteriaBuilder;
    }
}
