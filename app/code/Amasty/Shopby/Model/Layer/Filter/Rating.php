<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Layer\Filter\Resolver\FilterRequestDataResolver;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\StateException;
use Magento\Search\Api\SearchInterface;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\Api\Search\SearchResultInterface;

class Rating extends AbstractFilter
{
    const STARS = [
        1 => 20,
        2 => 40,
        3 => 60,
        4 => 80,
        5 => 100
    ];

    const INVALID_RATING = 6;
    const ATTRIBUTE_CODE = 'rating_summary';
    const REQUEST_VAR = 'rating';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var FilterRequestDataResolver
     */
    private $filterRequestDataResolver;

    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        ItemDataBuilder $itemDataBuilder,
        ScopeConfigInterface $scopeConfig,
        BlockFactory $blockFactory,
        SearchInterface $search,
        FilterRequestDataResolver $filterRequestDataResolver,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );
        $this->_requestVar = self::REQUEST_VAR;
        $this->scopeConfig = $scopeConfig;
        $this->blockFactory = $blockFactory;
        $this->search = $search;
        $this->filterRequestDataResolver = $filterRequestDataResolver;
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function apply(RequestInterface $request)
    {
        if ($this->filterRequestDataResolver->isApplied($this)) {
            return $this;
        }

        $value = $this->filterRequestDataResolver->getFilterParam($this);
        if (!isset(self::STARS[$value])) {
            return $this;
        }

        $this->filterRequestDataResolver->setCurrentValue($this, $value);
        $condition = self::STARS[$value];

        if ($value == self::INVALID_RATING) {
            $condition = new \Zend_Db_Expr("IS NULL");
        }

        $this->getLayer()->getProductCollection()->addFieldToFilter($this->getAttributeCode(), $condition);
        if ($value < count(self::STARS)) {
            $name = __('%1 stars & up', $value);
        } elseif ($value == 1) {
            $name = __('%1 star & up', $value);
        } else {
            $name = __('%1 stars', $value);
        }

        $item = $this->_createItem($name, $value);
        $this->getLayer()->getState()->addFilter($item);

        return $this;
    }

    /**
     * Get filter name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getName()
    {
        $label = $this->scopeConfig
            ->getValue('amshopby/rating_filter/label', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $label;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        $position = (int) $this->scopeConfig
            ->getValue('amshopby/rating_filter/position', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $position;
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if ($this->filterRequestDataResolver->isHidden($this)) {
            return [];
        }

        try {
            $optionsFacetedData = $this->getFacetedData();
        } catch (StateException $e) {
            $optionsFacetedData = [];
        }

        $listData = [];

        $allCount = 0;
        for ($i = count(self::STARS); $i >= 1; $i--) {
            $count = isset($optionsFacetedData[$i]) ? $optionsFacetedData[$i]['count'] : 0;

            $allCount += $count;

            $listData[] = [
                'label' => $this->getLabelHtml($i),
                'value' => $i,
                'count' => $allCount,
                'real_count' => $count,
            ];
        }

        foreach ($listData as $data) {
            if ($data['real_count'] < 1) {
                continue;
            }
            $this->itemDataBuilder->addItemData(
                $data['label'],
                $data['value'],
                $data['count']
            );
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * @param int $countStars
     *
     * @return string
     */
    private function getLabelHtml($countStars)
    {
        if ($countStars == self::INVALID_RATING) {
            return __('Not Yet Rated');
        }
        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $this->blockFactory->createBlock(\Magento\Framework\View\Element\Template::class);
        $block->setTemplate('Amasty_Shopby::layer/filter/item/rating.phtml');
        $block->setData('star', $countStars);
        $html = $block->toHtml();
        return $html;
    }

    /**
     * @return string
     */
    public function getAttributeCode(): string
    {
        return self::ATTRIBUTE_CODE;
    }

    /**
     * @return array
     */
    private function getFacetedData(): array
    {
        $collection = $this->getLayer()->getProductCollection();

        return $collection->getFacetedData($this->getAttributeCode(), $this->getSearchResult());
    }
    
    private function getSearchResult(): ?SearchResultInterface
    {
        $alteredQueryResponse = null;
        if ($this->filterRequestDataResolver->hasCurrentValue($this)) {
            $searchCriteria = $this->getLayer()->getProductCollection()->getSearchCriteria([$this->getAttributeCode()]);
            $alteredQueryResponse = $this->search->search($searchCriteria);
        }

        return $alteredQueryResponse;
    }
}
