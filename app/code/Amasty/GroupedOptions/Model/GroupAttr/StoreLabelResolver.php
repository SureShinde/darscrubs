<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\GroupAttr;

use Amasty\Base\Model\Serializer;
use Magento\Store\Model\StoreManagerInterface;

class StoreLabelResolver
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager, Serializer $serializer)
    {
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    public function chooseStoreLabel(string $label, ?int $storeId = null): ?string
    {
        $labels = $this->unserializeLabel($label);

        return $labels ? (string) $this->getLabelFromArray($labels, $storeId) : (string) $label;
    }

    private function unserializeLabel(string $label): ?array
    {
        try {
            $labels = $this->serializer->unserialize($label);
        } catch (\Exception $e) {
            return null;
        }

        return is_array($labels) ? $labels : [$labels];
    }

    private function getLabelFromArray(array $labels, ?int $storeId = null): string
    {
        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        $label = $labels[$storeId] ?? false;

        return $label ? (string) $label : $this->chooseDefaultLabel($labels);
    }

    private function chooseDefaultLabel(array $labels): ?string
    {
        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        return isset($labels[$storeId]) && !empty($labels[$storeId])
            ? (string) $labels[$storeId]
            : (string) array_shift($labels);
    }
}
