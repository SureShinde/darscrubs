<?php

namespace Amasty\ShopbySeo\Helper;

use Amasty\ShopbySeo\Model\SeoOptions;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class UrlParser extends AbstractHelper
{
    /**
     * @var  Data
     */
    protected $seoHelper;

    /**
     * @var string
     */
    protected $aliasDelimiter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SeoOptions
     */
    private $seoOptions;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        Data $seoHelper,
        Config $config,
        SeoOptions $seoOptions,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->seoHelper = $seoHelper;
        $this->config = $config;
        $this->aliasDelimiter = $this->config->getOptionSeparator() ?: '-';
        $this->seoOptions = $seoOptions;

        $this->storeManager = $storeManager;
    }

    /**
     * @param $seoPart
     * @return array
     */
    public function parseSeoPart($seoPart)
    {
        $seoPart = str_replace('/', $this->aliasDelimiter, $seoPart);
        if ($this->aliasDelimiter == $this->seoHelper->getSpecialChar()) {
            $aliases = $this->parseAliasesRecursive($seoPart);
            return $this->parseAliasesOldAlgorythm($aliases, $seoPart);
        }

        $aliases = $this->getAliases($seoPart);
        return $this->parseAliases($aliases);
    }

    /**
     * @param $seoPart
     * @return array
     */
    public function getAliases($seoPart)
    {
        $aliases = explode($this->aliasDelimiter, $seoPart);
        return $aliases;
    }

    /**
     * @param $seoPart
     * @return array
     */
    private function parseAliasesRecursive($seoPart)
    {
        if (!is_array($seoPart)) {
            $seoPart = explode($this->aliasDelimiter, $seoPart);
        }

        $aliases = [];
        $aliasGroup = [];
        if (empty($seoPart)) {
            return $aliases;
        }

        for ($i = count($seoPart) - 1; $i >= 0; $i--) {
            $aliasGroup[] = implode($this->aliasDelimiter, array_slice($seoPart, 0, $i + 1));
        }

        $aliases[] = $aliasGroup;
        array_shift($seoPart);
        return array_merge($aliases, $this->parseAliasesRecursive($seoPart));
    }

    private function replaceAliases(array $aliases): array
    {
        $store = $this->storeManager->getStore()->getId();
        foreach ($this->seoHelper->getAttributeUrlAliases() as $attribute => $alias) {
            if (isset($alias[$store])) {
                $key = array_search($alias[$store], $aliases);
                if ($key !== false) {
                    $aliases[$key] = $attribute;
                }
            }
        }

        return $aliases;
    }

    /**
     * @param array $aliases
     * @return array
     */
    protected function parseAliases($aliases)
    {
        $attributeOptionsData = $this->seoOptions->getData();
        $filterWord = $this->seoHelper->getFilterWord();
        $aliases = $this->replaceAliases($aliases);
        $paramsCount = 0;
        $params = [];
        $parsedAliases = [];
        $parsedAttributeNameCount = 0;

        foreach ($aliases as $key => $currentAlias) {
            if (in_array($currentAlias, array_keys($attributeOptionsData))
                && !in_array($currentAlias, $attributeOptionsData[$currentAlias])
                || $currentAlias == $filterWord) {
                unset($aliases[$key]);
                $parsedAttributeNameCount++;
                continue;
            }

            foreach ($attributeOptionsData as $attributeCode => $optionsData) {
                foreach ($optionsData as $optionId => $alias) {
                    if ($alias === $currentAlias) {
                        $parsedAliases[] = $currentAlias;
                        $params = $this->addParsedOptionToParams($optionId, $attributeCode, $params);
                        $paramsCount++;
                        continue 3;
                    }
                }
            }
        }

        if ($this->seoHelper->isIncludeAttributeName() && $parsedAttributeNameCount != count($params)) {
            return [];
        }

        return $paramsCount == count($aliases)  ? $params : [];
    }

    /**
     * @param array $aliases
     * @param string $seoPart
     * @return array
     */
    protected function parseAliasesOldAlgorythm($aliases, $seoPart)
    {
        $attributeOptionsData = $this->seoOptions->getData();
        $filterWord = $this->seoHelper->getFilterWord();
        $paramsCount = 0;
        $params = [];
        $parsedAliases = [];
        foreach ($aliases as $groupKey => $aliasGroup) {
            foreach ($aliasGroup as $key => $currentAlias) {
                foreach ($attributeOptionsData as $attributeCode => $optionsData) {
                    foreach ($optionsData as $optionId => $alias) {
                        if ($alias === $currentAlias) {
                            $parsedAliases[] = $currentAlias;
                            $params = $this->addParsedOptionToParams($optionId, $attributeCode, $params);
                            $paramsCount++;
                            continue 4;
                        }
                    }
                }

            }
        }

        $requestedAliases = $this->replaceAliases(explode($this->aliasDelimiter, $seoPart));
        $includeAttributeName = $this->seoHelper->isIncludeAttributeName();
        if ($includeAttributeName || !empty($filterWord)) {
            foreach ($requestedAliases as $key => $alias) {
                if ($alias == $filterWord
                    || ($includeAttributeName && in_array($alias, array_keys($attributeOptionsData)))
                ) {
                    unset($requestedAliases[$key]);
                    continue;
                }
            }
        }

        return implode($this->aliasDelimiter, $requestedAliases) == implode($this->aliasDelimiter, $parsedAliases)
            ? $params : [];
    }

    /**
     * @param $value
     * @param $paramName
     * @param $params
     * @return mixed
     */
    protected function addParsedOptionToParams($value, $paramName, $params)
    {
        if (array_key_exists($paramName, $params)) {
            $params[$paramName] .= ',' . $value;
        } else {
            $params[$paramName] = '' . $value;
        }

        return $params;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function checkSeoParams(array $params = [])
    {
        $attributeOptionsData = $this->seoOptions->getData();
        foreach ($params as $paramName => $paramValue) {
            if (isset($attributeOptionsData[$paramName])) {
                return true;
            }
        }

        return false;
    }
}
