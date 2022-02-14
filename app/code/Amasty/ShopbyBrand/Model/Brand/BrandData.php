<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Brand;

use Magento\Framework\DataObject;

class BrandData extends DataObject implements BrandDataInterface
{
    /**
     * @return bool
     */
    public function getIsShowInWidget(): bool
    {
        return (bool) $this->getDataByKey(self::IS_SHOW_IN_WIDGET);
    }

    /**
     * @param bool $isShowInWidget
     */
    public function setIsShowInWidget(bool $isShowInWidget): void
    {
        $this->setData(self::IS_SHOW_IN_WIDGET, $isShowInWidget);
    }

    /**
     * @return bool
     */
    public function getIsShowInSlider(): bool
    {
        return (bool) $this->getDataByKey(self::IS_SHOW_IN_SLIDER);
    }

    /**
     * @param bool $isShowInSlider
     */
    public function setIsShowInSlider(bool $isShowInSlider): void
    {
        $this->setData(self::IS_SHOW_IN_SLIDER, $isShowInSlider);
    }

    /**
     * @return int
     */
    public function getBrandId(): int
    {
        return (int) $this->getDataByKey(self::BRAND_ID);
    }

    /**
     * @param int $brandId
     */
    public function setBrandId(int $brandId): void
    {
        $this->setData(self::BRAND_ID, $brandId);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return (string) $this->getDataByKey(self::LABEL);
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->setData(self::LABEL, $label);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return (string) $this->getDataByKey(self::URL);
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->setData(self::URL, $url);
    }

    /**
     * @return string
     */
    public function getImg(): string
    {
        return (string) $this->getDataByKey(self::IMG);
    }

    /**
     * @param string $img
     */
    public function setImg(string $img): void
    {
        $this->setData(self::IMG, $img);
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return (string) $this->getDataByKey(self::IMAGE);
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->setData(self::IMAGE, $image);
    }

    /**
     * @return string
     */
    public function getAlt(): string
    {
        return (string) $this->getDataByKey(self::ALT);
    }

    /**
     * @param string $alt
     */
    public function setAlt(string $alt): void
    {
        $this->setData(self::ALT, $alt);
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return (int) $this->getDataByKey(self::POSITION);
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->setData(self::POSITION, $position);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getDataByKey(self::DESCRIPTION);
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @return string
     */
    public function getShortDescription(): string
    {
        return (string) $this->getDataByKey(self::SHORT_DESCRIPTION);
    }

    /**
     * @param string $shortDescription
     */
    public function setShortDescription(string $shortDescription): void
    {
        $this->setData(self::SHORT_DESCRIPTION, $shortDescription);
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return (int) $this->getDataByKey(self::COUNT);
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->setData(self::COUNT, $count);
    }
}
