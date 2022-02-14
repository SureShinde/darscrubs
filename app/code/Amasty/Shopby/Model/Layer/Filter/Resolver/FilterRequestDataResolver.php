<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter\Resolver;

use Amasty\Shopby\Model\Request;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;

class FilterRequestDataResolver
{
    /**
     * @var \Amasty\Shopby\Model\Request
     */
    private $shopbyRequest;

    /**
     * @var FilterSettingResolver
     */
    private $settingResolver;

    /**
     * @var array
     */
    private $currentValue = [];

    public function __construct(
        Request $shopbyRequest,
        FilterSettingResolver $settingResolver
    ) {
        $this->shopbyRequest = $shopbyRequest;
        $this->settingResolver = $settingResolver;
    }

    /**
     * @param FilterInterface
     * @param mixed $currentValue
     */
    public function setCurrentValue(FilterInterface $filter, $currentValue): void
    {
        $this->currentValue[$filter->getRequestVar()] = $currentValue;
    }

    /**
     * @param FilterInterface $filter
     * @return mixed
     */
    public function getCurrentValue(FilterInterface $filter)
    {
        return $this->currentValue[$filter->getRequestVar()] ?? null;
    }

    /**
     * @param FilterInterface $filter
     * @param bool $force
     * @return bool
     */
    public function isVisibleWhenSelected(FilterInterface $filter, bool $force = false): bool
    {
        $keepSingleChoice = $this->settingResolver->isSingleChoiceMode();

        return $keepSingleChoice || ($this->settingResolver->isMultiselectAllowed($filter) && !$force);
    }

    /**
     * @return bool is filter applied
     */
    public function hasCurrentValue(FilterInterface $filter): bool
    {
        return isset($this->currentValue[$filter->getRequestVar()]);
    }

    /**
     * @param FilterInterface $filter
     * @return bool
     */
    public function isApplied(FilterInterface $filter): bool
    {
        foreach ($filter->getLayer()->getState()->getFilters() as $appliedFilter) {
            if ($filter->getRequestVar() == $appliedFilter->getFilter()->getRequestVar()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param FilterInterface $filter
     * @param array $itemsData
     * @return array
     */
    public function getReducedItemsData(FilterInterface $filter, array $itemsData): array
    {
        return $this->isApplied($filter) ? $itemsData : [];
    }

    /**
     * @param FilterInterface $filter
     * @param bool $force
     * @return bool
     */
    public function isHidden(FilterInterface $filter, bool $force = false): bool
    {
        return (bool)$this->getFilterParam($filter) && !$this->isVisibleWhenSelected($filter, $force);
    }

    /**
     * @param FilterInterface $filter
     * @return mixed
     */
    public function getFilterParam(FilterInterface $filter)
    {
        return $this->shopbyRequest->getFilterParam($filter);
    }

    /**
     * @param string $paramName
     * @return mixed
     */
    public function getDeltaParam(string $paramName)
    {
        return $this->shopbyRequest->getDeltaParam($paramName);
    }
}
