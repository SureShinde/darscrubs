<?php

namespace Amasty\ShopbyBase\Api\Data;

interface FilterSettingInterface
{
    public const FILTER_SETTING_ID = 'setting_id';
    public const FILTER_CODE = 'filter_code';
    public const DISPLAY_MODE = 'display_mode';
    public const IS_MULTISELECT = 'is_multiselect';
    public const IS_SEO_SIGNIFICANT = 'is_seo_significant';
    public const INDEX_MODE = 'index_mode';
    public const FOLLOW_MODE = 'follow_mode';
    public const REL_NOFOLLOW = 'rel_nofollow';
    public const EXPAND_VALUE = 'is_expanded';
    public const SORT_OPTIONS_BY = 'sort_options_by';
    public const SHOW_PRODUCT_QUANTITIES = 'show_product_quantities';
    public const IS_SHOW_SEARCH_BOX = 'is_show_search_box';
    public const NUMBER_UNFOLDED_OPTIONS = 'number_unfolded_options';
    public const TOOLTIP = 'tooltip';
    public const ADD_FROM_TO_WIDGET = 'add_from_to_widget';
    public const IS_USE_AND_LOGIC = 'is_use_and_logic';
    public const VISIBLE_IN_CATEGORIES = 'visible_in_categories';
    public const CATEGORIES_FILTER = 'categories_filter';
    public const ATTRIBUTES_FILTER = 'attributes_filter';
    public const ATTRIBUTES_OPTIONS_FILTER = 'attributes_options_filter';
    public const BLOCK_POSITION = 'block_position';
    public const SHOW_ICONS_ON_PRODUCT = 'show_icons_on_product';
    public const UNITS_LABEL = 'units_label';
    public const USE_CURRENCY_SYMBOL = 'units_label_use_currency_symbol';
    public const SHOW_FEATURED_ONLY = 'show_featured_only';
    public const CATEGORY_TREE_DISPLAY_MODE = 'category_tree_display_mode';
    public const LIMIT_OPTIONS_SHOW_SEARCH_BOX = 'limit_options_show_search_box';
    public const TOP_POSITION = 'top_position';
    public const SIDE_POSITION = 'side_position';
    public const POSITION_LABEL = 'position_label';
    public const SLIDER_MIN = 'slider_min';
    public const SLIDER_MAX = 'slider_max';

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int|null
     */
    public function getDisplayMode();

    /**
     * @return int
     */
    public function getFollowMode();

    /**
     * @return string|null
     */
    public function getFilterCode();

    /**
     * @return int
     */
    public function getIndexMode();

    /**
     * @return int
     */
    public function getRelNofollow();

    /**
     * @return bool
     */
    public function isAddNofollow();

    /**
     * @return bool|null
     */
    public function getAddFromToWidget();

    /**
     * @return bool
     */
    public function isMultiselect();

    /**
     * @return int
     */
    public function getSeoSignificant(): int;

    /**
     * @return bool|null
     */
    public function isExpanded();

    /**
     * @return int
     */
    public function getSortOptionsBy();

    /**
     * @return int
     */
    public function getShowProductQuantities();

    /**
     * @param int $optionsCount
     * @return bool
     */
    public function isShowSearchBox($optionsCount);

    /**
     * @return mixed
     */
    public function getNumberUnfoldedOptions();

    /**
     * @return bool
     */
    public function isUseAndLogic();

    /**
     * @return string
     */
    public function getTooltip();

    /**
     * @return string
     */
    public function getVisibleInCategories();

    /**
     * @return array
     */
    public function getCategoriesFilter();

    /**
     * @return array
     */
    public function getAttributesFilter();

    /**
     * @return array
     */
    public function getAttributesOptionsFilter();

    /**
     * @return int
     */
    public function getBlockPosition();

    /**
     * @return mixed
     */
    public function getShowIconsOnProduct();

    /**
     * @return string
     */
    public function getUnitsLabel();

    /**
     * @return int
     */
    public function getUnitsLabelUseCurrencySymbol();

    /**
     * @return int
     */
    public function getCategoryTreeDisplayMode();

    /**
     * @return int
     */
    public function getTopPosition(): int;

    /**
     * @return int
     */
    public function getSidePosition(): int;

    /**
     * @param int $id
     * @return FilterSettingInterface
     */
    public function setId($id);

    /**
     * @param int $displayMode
     * @return FilterSettingInterface
     */
    public function setDisplayMode($displayMode);

    /**
     * @param int $indexMode
     * @return FilterSettingInterface
     */
    public function setIndexMode($indexMode);

    /**
     * @param int $relNofollow
     * @return FilterSettingInterface
     */
    public function setRelNofollow($relNofollow);

    /**
     * @param int $followMode
     * @return FilterSettingInterface
     */
    public function setFollowMode($followMode);

    /**
     * @param bool $isMultiselect
     * @return FilterSettingInterface
     */
    public function setIsMultiselect($isMultiselect);

    /**
     * @param int $seoSignificant
     */
    public function setSeoSignificant(int $seoSignificant): void;

    /**
     * @param bool $isExpanded
     *
     * @return FilterSettingInterface
     */
    public function setIsExpanded($isExpanded);

    /**
     * @param string $filterCode
     * @return FilterSettingInterface
     */
    public function setFilterCode($filterCode);

    /**
     * @param int $sortOptionsBy
     * @return FilterSettingInterface
     */
    public function setSortOptionsBy($sortOptionsBy);

    /**
     * @param int $showProductQuantities
     * @return FilterSettingInterface
     */
    public function setShowProductQuantities($showProductQuantities);

    /**
     * @param bool $isShowSearchBox
     * @return FilterSettingInterface
     */
    public function setIsShowSearchBox($isShowSearchBox);

    /**
     * @param int $numberOfUnfoldedOptions
     * @return FilterSettingInterface
     */
    public function setNumberUnfoldedOptions($numberOfUnfoldedOptions);

    /**
     * @param string $tooltip
     *
     * @return FilterSettingInterface
     */
    public function setTooltip($tooltip);

    /**
     * @param string $visibleInCategories
     * @return FilterSettingInterface
     */
    public function setVisibleInCategories($visibleInCategories);

    /**
     * @param array $categoriesFilter
     * @return FilterSettingInterface
     */
    public function setCategoriesFilter($categoriesFilter);

    /**
     * @param array $attributesFilter
     * @return FilterSettingInterface
     */
    public function setAttributesFilter($attributesFilter);

    /**
     * @param array $attributesOptionsFilter
     * @return FilterSettingInterface
     */
    public function setAttributesOptionsFilter($attributesOptionsFilter);

    /**
     * @param bool $addFromToWidget
     *
     * @return FilterSettingInterface
     */
    public function setAddFromToWidget($addFromToWidget);

    /**
     * @param bool $isUseAndLogic
     *
     * @return FilterSettingInterface
     */
    public function setIsUseAndLogic($isUseAndLogic);

    /**
     * @param int $blockPosition
     *
     * positions may see in \Amasty\ShopbyBase\Model\Source\FilterPlacedBlock
     * @return FilterSettingInterface
     */
    public function setBlockPosition($blockPosition);

    /**
     * @param string|array $isShowLinks
     * @return FilterSettingInterface
     */
    public function setShowIconsOnProduct($isShowLinks);

    /**
     * @param string $label
     * @return FilterSettingInterface
     */
    public function setUnitsLabel($label);

    /**
     * @param int $useCurrency
     * @return FilterSettingInterface
     */
    public function setUnitsLabelUseCurrencySymbol($useCurrency);

    /**
     * @param int $displayMode
     * @return FilterSettingInterface
     */
    public function setCategoryTreeDisplayMode($displayMode);

    /**
     * @return int
     */
    public function getPositionLabel(): int;

    /**
     * @param int $positionLabel
     */
    public function setPositionLabel(int $positionLabel): void;

    /**
     * @return float
     */
    public function getSliderMin(): float;

    /**
     * @return float
     */
    public function getSliderMax(): float;

    /**
     * @param int $topPosition
     * @return void
     */
    public function setTopPosition(int $topPosition): void;

    /**
     * @param int $sidePosition
     * @return void
     */
    public function setSidePosition(int $sidePosition): void;
}
