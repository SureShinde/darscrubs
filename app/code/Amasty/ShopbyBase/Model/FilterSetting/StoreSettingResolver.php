<?php

declare(strict_types=1);

namespace Amasty\ShopbyBase\Model\FilterSetting;

use Amasty\Base\Model\Serializer;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class StoreSettingResolver
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

    /**
     * TODO: refactor
     *
     * @param string $label
     * @param int|null $storeId
     * @return string
     */
    public function chooseStoreLabel(string $label, ?int $storeId = null): string
    {
        $labels = $this->unserializeLabel($label);

        return $labels && is_array($labels) ? $this->getLabelFromArray($labels, $storeId) : $label;
    }

    private function unserializeLabel(string $label): ?array
    {
        try {
            $labels = $this->serializer->unserialize($label);
        } catch (\Exception $e) {
            return null;
        }

        return is_array($labels) ? $labels : null;
    }

    private function getLabelFromArray(array $labels, ?int $storeId = null): string
    {
        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        $label = $labels[$storeId] ?? ($labels[Store::DEFAULT_STORE_ID] ?? array_shift($labels));

        return (string) $label;
    }
}
