<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Model\SeoOptionsModifier;

use Amasty\ShopbySeo\Helper\Data;
use Magento\Catalog\Model\Product\Url as ProductUrl;

class UniqueBuilder
{
    const DEFAULT_FORMAT = '-';
    
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var Data
     */
    private $seoHelper;

    /**
     * @var ProductUrl
     */
    private $productUrl;

    public function __construct(Data $seoHelper, ProductUrl $productUrl)
    {
        $this->seoHelper = $seoHelper;
        $this->productUrl = $productUrl;
    }

    public function execute(string $value, string $optionId = ''): string
    {
        // @codingStandardsIgnoreLine
        $value = html_entity_decode($value, ENT_QUOTES);
        $formattedValue = $this->productUrl->formatUrlKey($value) ?: self::DEFAULT_FORMAT;
        $formattedValue = str_replace('-', $this->seoHelper->getSpecialChar(), $formattedValue);

        $unique = $formattedValue;
        $i = 1;
        while (array_key_exists($unique, $this->cache)) {
            if ($this->cache[$unique] !== $optionId) {
                $unique = $formattedValue . $this->seoHelper->getSpecialChar() . ($i++);
            } else {
                unset($this->cache[$unique]);
            }
        }

        $this->cache[$unique] = $optionId;

        return $unique;
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
