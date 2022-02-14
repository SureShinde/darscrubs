<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Attribute
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    private $brandOptions;

    public function __construct(ConfigProvider $configProvider, AttributeRepository $attributeRepository)
    {
        $this->configProvider = $configProvider;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return ProductAttributeInterface|IdentityInterface|null
     */
    public function getAttribute(): ?ProductAttributeInterface
    {
        $attributeCode = $this->configProvider->getBrandAttributeCode();

        if (!$attributeCode) {
            return null;
        }

        try {
            return $this->attributeRepository->get($attributeCode);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return AttributeOptionInterface[]|null
     */
    public function getOptions(): ?array
    {
        if ($this->brandOptions === null) {
            $this->brandOptions = [];
            $attribute = $this->getAttribute();
            if ($attribute) {
                $this->brandOptions = $attribute->getOptions();
                array_shift($this->brandOptions);
            }
        }

        return $this->brandOptions;
    }
}
