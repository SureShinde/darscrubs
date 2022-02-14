<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model\Brand;

interface BrandDataInterface
{
    public const IS_SHOW_IN_WIDGET = 'is_show_in_widget';

    public const IS_SHOW_IN_SLIDER = 'is_show_in_slider';

    public const BRAND_ID = 'brand_id';

    public const LABEL = 'label';

    public const URL = 'url';

    public const IMG = 'img';

    public const IMAGE = 'image';

    public const ALT = 'alt';

    public const POSITION = 'position';

    public const DESCRIPTION = 'description';

    public const SHORT_DESCRIPTION = 'short_description';

    public const COUNT = 'cnt';

    /**
     * @return bool
     */
    public function getIsShowInWidget(): bool;

    /**
     * @param bool $isShowInWidget
     */
    public function setIsShowInWidget(bool $isShowInWidget): void;

    /**
     * @return bool
     */
    public function getIsShowInSlider(): bool;

    /**
     * @param bool $isShowInSlider
     */
    public function setIsShowInSlider(bool $isShowInSlider): void;

    /**
     * @return int
     */
    public function getBrandId(): int;

    /**
     * @param int $brandId
     */
    public function setBrandId(int $brandId): void;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @param string $label
     */
    public function setLabel(string $label): void;

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     */
    public function setUrl(string $url): void;

    /**
     * @return string
     */
    public function getImg(): string;

    /**
     * @param string $img
     */
    public function setImg(string $img): void;

    /**
     * @return string
     */
    public function getImage(): string;

    /**
     * @param string $image
     */
    public function setImage(string $image): void;

    /**
     * @return string
     */
    public function getAlt(): string;

    /**
     * @param string $alt
     */
    public function setAlt(string $alt): void;

    /**
     * @return int
     */
    public function getPosition(): int;

    /**
     * @param int $position
     */
    public function setPosition(int $position): void;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @param string $description
     */
    public function setDescription(string $description): void;

    /**
     * @return string
     */
    public function getShortDescription(): string;

    /**
     * @param string $shortDescription
     */
    public function setShortDescription(string $shortDescription): void;

    /**
     * @return int
     */
    public function getCount(): int;

    /**
     * @param int $count
     */
    public function setCount(int $count): void;
}
