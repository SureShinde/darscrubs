<?php

declare(strict_types=1);

namespace Amasty\Shopby\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Amasty\Shopby\Block\Adminhtml\Form\Renderer\Fieldset\MultiStore;
use Amasty\Shopby\Block\Adminhtml\Product\Attribute\Edit\Tab\Shopby\Multiselect;
use Amasty\Shopby\Helper\Category;
use Amasty\Shopby\Model\Source\Expand;
use Amasty\Shopby\Model\Source\PositionLabel;
use Amasty\Shopby\Model\Source\RenderCategoriesLevel;
use Amasty\Shopby\Model\Source\RenderCategoriesTree;
use Amasty\Shopby\Model\Source\SubcategoriesExpand;
use Amasty\Shopby\Model\Source\SubcategoriesView;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Block\Widget\Form\Element\Dependence;
use Amasty\Shopby\Helper\FilterSetting as FilterSettingHelper;
use Amasty\ShopbyBase\Model\FilterSetting;
use Amasty\ShopbyBase\Model\FilterSettingFactory;
use Amasty\Shopby\Model\Source\VisibleInCategory;
use Amasty\Shopby\Model\Source\Category as CategorySource;
use Amasty\Shopby\Model\Source\Attribute as AttributeSource;
use Amasty\Shopby\Model\Source\Attribute\Option as AttributeOptionSource;
use Amasty\Shopby\Model\Source\DisplayMode;
use Amasty\Shopby\Model\Source\MeasureUnit;
use Amasty\Shopby\Model\Source\MultipleValuesLogic;
use Amasty\Shopby\Model\Source\ShowProductQuantities;
use Amasty\Shopby\Model\Source\CategoryTreeDisplayMode;
use Amasty\Shopby\Model\Source\SortOptionsBy;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Amasty\ShopbySeo\Model\Source\RelNofollow;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\Data\Form\Element\Fieldset;
use Amasty\Shopby\Model\Source\FilterPlacedBlock;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Swatches\Model\Swatch;

class Shopby extends \Magento\Backend\Block\Widget\Form\Generic
{
    const MAX_ATTRIBUTE_OPTIONS_COUNT = 500;

    const FIELD_FRONTEND_INPUT = 'frontend_input';

    const DISPLAY_MODE = 'display_mode';

    const YES_NO_NEGATIVE_VALUE = '0';
    const YES_NO_POSITIVE_VALUE = '1';

    /**
     * @var Yesno
     */
    protected $yesNo;

    /**
     * @var  DisplayMode
     */
    protected $displayMode;

    /**
     * @var  MeasureUnit
     */
    protected $measureUnitSource;

    /**
     * @var  MultipleValuesLogic
     */
    protected $multipleValuesLogic;

    /**
     * @var  FilterSetting
     */
    protected $setting;

    /**
     * @var Attribute $attributeObject
     */
    protected $attributeObject;

    /**
     * @var SortOptionsBy
     */
    protected $sortOptionsBy;

    /**
     * @var ShowProductQuantities
     */
    protected $showProductQuantities;

    /**
     * @var CategoryTreeDisplayMode
     */
    protected $categoryTreeDisplayMode;

    /**
     * @var FieldFactory
     */
    protected $dependencyFieldFactory;

    /**
     * @var VisibleInCategory
     */
    protected $visibleInCategory;

    /**
     * @var CategorySource
     */
    protected $categorySource;

    /**
     * @var AttributeSource
     */
    protected $attributeSource;

    /**
     * @var AttributeOptionSource
     */
    protected $attributeOptionSource;

    /**
     * @var FilterPlacedBlock
     */
    protected $filterPlacedBlockSource;

    /**
     * @var SubcategoriesView
     */
    protected $subcategoriesViewSource;

    /**
     * @var SubcategoriesExpand
     */
    protected $subcategoriesExpandSource;

    /**
     * @var RenderCategoriesLevel
     */
    protected $renderCategoriesLevelSource;

    /**
     * @var FilterSettingHelper
     */
    protected $filterSettingHelper;

    /**
     * @var RenderCategoriesTree
     */
    protected $renderCategoriesTreeSource;

    /**
     * @var PositionLabel
     */
    protected $positionLabelSource;

    /**
     * @var Expand
     */
    private $expandSource;

    /**
     * Serializer that allow convert arrays to string.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Yesno $yesNo,
        DisplayMode $displayMode,
        CategorySource $categorySource,
        MeasureUnit $measureUnitSource,
        AttributeSource $attributeSource,
        AttributeOptionSource $attributeOptionSource,
        FilterSettingFactory $settingFactory,
        SortOptionsBy $sortOptionsBy,
        ShowProductQuantities $showProductQuantities,
        FieldFactory $dependencyFieldFactory,
        MultipleValuesLogic $multipleValuesLogic,
        FilterPlacedBlock $filterPlacedBlockSource,
        SubcategoriesView $subcategoriesViewSource,
        SubcategoriesExpand $subcategoriesExpandSource,
        RenderCategoriesLevel $renderCategoriesLevelSource,
        CategoryTreeDisplayMode $categoryTreeDisplayMode,
        RenderCategoriesTree $renderCategoriesTreeSource,
        PositionLabel $positionLabelSource,
        FilterSettingHelper $filterSettingHelper,
        Expand $expandSource,
        VisibleInCategory $visibleInCategory,
        Json $serializer,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->displayMode = $displayMode;
        $this->measureUnitSource = $measureUnitSource;
        $this->setting = $settingFactory->create();
        $this->attributeObject = $registry->registry('entity_attribute');
        $this->sortOptionsBy = $sortOptionsBy;
        $this->showProductQuantities = $showProductQuantities;
        $this->dependencyFieldFactory = $dependencyFieldFactory;
        $this->multipleValuesLogic = $multipleValuesLogic;
        $this->categorySource = $categorySource->setEmptyOption(false);
        $this->attributeSource = $attributeSource->skipAttributeId($this->attributeObject->getId());
        $this->attributeOptionSource = $attributeOptionSource->skipAttributeId($this->attributeObject->getId());
        $this->filterPlacedBlockSource = $filterPlacedBlockSource;
        $this->subcategoriesViewSource = $subcategoriesViewSource;
        $this->subcategoriesExpandSource = $subcategoriesExpandSource;
        $this->renderCategoriesTreeSource = $renderCategoriesTreeSource;
        $this->renderCategoriesLevelSource = $renderCategoriesLevelSource;
        $this->filterSettingHelper = $filterSettingHelper;
        $this->displayMode->setAttribute($this->attributeObject);
        $this->categoryTreeDisplayMode = $categoryTreeDisplayMode;
        $this->positionLabelSource = $positionLabelSource;
        $this->expandSource = $expandSource;
        $this->visibleInCategory = $visibleInCategory;
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->configProvider = $configProvider;
    }

    private function addDisplayModeField(Fieldset $fieldsetDisplayProperties, Dependence $dependence): ?AbstractElement
    {
        if ($this->isNeedDisplayMode()) {
            $displayModeField = $fieldsetDisplayProperties->addField(
                self::DISPLAY_MODE,
                'select',
                [
                    'name' => self::DISPLAY_MODE,
                    'label' => __('Display Mode'),
                    'title' => __('Display Mode'),
                    'values' => $this->displayMode->toOptionArray(),
                    'note' => '&nbsp;'
                ]
            );

            if ($this->displayMode->showDefaultSwatchOptions()
                || $this->attributeObject->getFrontendInput() == DisplayMode::ATTRUBUTE_PRICE
            ) {
                $dependence->addGroupValues(
                    $displayModeField->getName(),
                    self::FIELD_FRONTEND_INPUT,
                    $this->displayMode->getInputTypeMap(),
                    $this->displayMode->getAllOptionsDependencies()
                );
            }
        }

        return $displayModeField ?? null;
    }

    private function addFromToField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        if ($displayModeField) {
            $addFromToWidget = $fieldsetDisplayProperties->addField(
                'add_from_to_widget',
                'select',
                [
                    'name' => 'add_from_to_widget',
                    'label' => __('Add From-To Widget'),
                    'title' => __('Add From-To Widget'),
                    'values' => $this->yesNo->toOptionArray()
                ]
            );
            $valuesMode = [
                DisplayMode::MODE_DEFAULT,
                DisplayMode::MODE_DROPDOWN,
                DisplayMode::MODE_SLIDER
            ];
            /**
             * dependency means that all Display Modes support widget except "From-To Only" mode
             */
            $dependence->addFieldMap(
                $addFromToWidget->getHtmlId(),
                $addFromToWidget->getName()
            );

            $dependence->addFieldToGroup($addFromToWidget->getName(), DisplayMode::ATTRUBUTE_PRICE);

            $dependence->addFieldDependence(
                $addFromToWidget->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ',',
                            'value' => implode(',', $valuesMode),
                            'negative' => false,
                            'group' => 'price'
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );

            $dependence->addFieldMap(
                $displayModeField->getHtmlId(),
                $displayModeField->getName()
            );
        }

        return $addFromToWidget ?? null;
    }

    private function addMinSliderField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        if ($displayModeField) {
            $sliderMinField = $fieldsetDisplayProperties->addField(
                'slider_min',
                'text',
                [
                    'name' => 'slider_min',
                    'label' => __('Minimum Slider Value'),
                    'title' => __('Minimum Slider Value'),
                    'class' => 'validate-zero-or-greater validate-number',
                    'note' => __('Please specify the min value to limit the slider, e.g. <$10')
                ]
            );

            $dependence->addFieldMap(
                $sliderMinField->getHtmlId(),
                $sliderMinField->getName()
            );

            $dependence->addFieldDependence(
                $sliderMinField->getName(),
                $displayModeField->getName(),
                DisplayMode::MODE_SLIDER
            );
        }

        return $sliderMinField ?? null;
    }

    private function addMaxSliderField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        if ($displayModeField) {
            $sliderMaxField = $fieldsetDisplayProperties->addField(
                'slider_max',
                'text',
                [
                    'name' => 'slider_max',
                    'label' => __('Maximum Slider Value'),
                    'title' => __('Maximum Slider Value'),
                    'class' => 'validate-greater-than-zero validate-number',
                    'note' => __('Please specify the max value to limit the slider, e.g. >$999')
                ]
            );

            $dependence->addFieldMap(
                $sliderMaxField->getHtmlId(),
                $sliderMaxField->getName()
            );

            $dependence->addFieldDependence(
                $sliderMaxField->getName(),
                $displayModeField->getName(),
                DisplayMode::MODE_SLIDER
            );
        }

        return $sliderMaxField ?? null;
    }

    private function addStepSliderField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        if ($displayModeField) {
            $sliderStepField = $fieldsetDisplayProperties->addField(
                'slider_step',
                'text',
                [
                    'name' => 'slider_step',
                    'label' => __('Slider Step'),
                    'title' => __('Slider Step'),
                    'class' => 'validate-zero-or-greater'
                ]
            );

            $dependence->addFieldMap(
                $sliderStepField->getHtmlId(),
                $sliderStepField->getName()
            );

            $dependence->addFieldDependence(
                $sliderStepField->getName(),
                $displayModeField->getName(),
                DisplayMode::MODE_SLIDER
            );
        }

        return $sliderStepField ?? null;
    }

    private function addPriceFields(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): void {
        if ($displayModeField && $this->attributeObject->getAttributeCode() != DisplayMode::ATTRUBUTE_PRICE) {
            ////for decimal
            $valuesMode = [
                DisplayMode::MODE_DEFAULT,
                DisplayMode::MODE_DROPDOWN,
                DisplayMode::MODE_SLIDER,
                DisplayMode::MODE_FROM_TO_ONLY
            ];

            $useCurrencySymbolField = $fieldsetDisplayProperties->addField(
                'units_label_use_currency_symbol',
                'select',
                [
                    'name' => 'units_label_use_currency_symbol',
                    'label' => __('Measure Units'),
                    'title' => __('Measure Units'),
                    'values' => $this->measureUnitSource->toOptionArray(),
                ]
            );
            $dependence->addFieldMap(
                $useCurrencySymbolField->getHtmlId(),
                $useCurrencySymbolField->getName()
            );
            $dependence->addFieldToGroup($useCurrencySymbolField->getName(), DisplayMode::ATTRUBUTE_PRICE);

            $unitsLabelField = $fieldsetDisplayProperties->addField(
                'units_label',
                'text',
                [
                    'name' => 'units_label',
                    'label' => __('Unit Label'),
                    'title' => __('Unit Label'),
                ]
            );

            $dependence->addFieldMap(
                $unitsLabelField->getHtmlId(),
                $unitsLabelField->getName()
            );

            $dependence->addFieldDependence(
                $unitsLabelField->getName(),
                $useCurrencySymbolField->getName(),
                MeasureUnit::CUSTOM
            );
            $dependence->addFieldToGroup($unitsLabelField->getName(), DisplayMode::ATTRUBUTE_PRICE);

            $positionLabelField = $fieldsetDisplayProperties->addField(
                'position_label',
                'select',
                [
                    'name' => 'position_label',
                    'label' => __('Position Label'),
                    'title' => __('Position Label'),
                    'values' => $this->positionLabelSource->toOptionArray(),
                ]
            );

            $dependence->addFieldMap(
                $positionLabelField->getHtmlId(),
                $positionLabelField->getName()
            );

            $dependence->addFieldDependence(
                $positionLabelField->getName(),
                $useCurrencySymbolField->getName(),
                MeasureUnit::CUSTOM
            );
            $dependence->addFieldToGroup($positionLabelField->getName(), DisplayMode::ATTRUBUTE_PRICE);

            $dependence->addFieldDependence(
                $positionLabelField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ',',
                            'value' => implode(",", $valuesMode),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
            $dependence->addFieldDependence(
                $unitsLabelField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ',',
                            'value' => implode(",", $valuesMode),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
            $dependence->addFieldDependence(
                $useCurrencySymbolField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ';',
                            'value' => implode(";", $valuesMode),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }
    }

    private function addBlockPositionField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence
    ): ?AbstractElement {
        $blockPosition = $fieldsetDisplayProperties->addField(
            'block_position',
            'select',
            [
                'name' => 'block_position',
                'label' => __('Show in the Block'),
                'title' => __('Show in the Block'),
                'values' => $this->filterPlacedBlockSource->toOptionArray(),
            ]
        );

        $topPosition = $fieldsetDisplayProperties->addField(
            'top_position',
            'text',
            [
                'name' => 'top_position',
                'label' => __('Position in Top'),
                'title' => __('Position in Top'),
                'class' => 'validate-number',
                'note' => __('Specify sorting order in the top navigation block.' .
                    ' Current configuration overrides a default attribute\'s Position setting.')
            ]
        );

        $sidePosition = $fieldsetDisplayProperties->addField(
            'side_position',
            'text',
            [
                'name' => 'side_position',
                'label' => __('Position in Sidebar'),
                'title' => __('Position in Sidebar'),
                'class' => 'validate-number',
                'note' => __('Specify sorting order in the sidebar navigation block. ' .
                    'Current configuration overrides a default attribute\'s Position setting.')
            ]
        );

        $dependence->addFieldMap(
            $blockPosition->getHtmlId(),
            $blockPosition->getName()
        );
        $dependence->addFieldMap(
            $topPosition->getHtmlId(),
            $topPosition->getName()
        );
        $dependence->addFieldMap(
            $sidePosition->getHtmlId(),
            $sidePosition->getName()
        );

        $dependence->addFieldDependence(
            $topPosition->getName(),
            $blockPosition->getName(),
            FilterPlacedBlock::POSITION_BOTH
        );
        $dependence->addFieldDependence(
            $sidePosition->getName(),
            $blockPosition->getName(),
            FilterPlacedBlock::POSITION_BOTH
        );

        return $blockPosition ?? null;
    }

    private function addSortOptionsField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        $sortOptionsByField = $fieldsetDisplayProperties->addField(
            'sort_options_by',
            'select',
            [
                'name' => 'sort_options_by',
                'label' => __('Sort Options By'),
                'title' => __('Sort Options By'),
                'values' => $this->sortOptionsBy->toOptionArray(),
            ]
        );

        $dependence->addFieldMap(
            $sortOptionsByField->getHtmlId(),
            $sortOptionsByField->getName()
        );

        if ($displayModeField) {
            $dependence->addFieldDependence(
                $sortOptionsByField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' =>
                            [
                                'value' => (string)DisplayMode::MODE_SLIDER,
                                'negative' => true
                            ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }
        $dependence->addFieldToGroup($sortOptionsByField->getName(), DisplayMode::ATTRUBUTE_DEFAULT);

        return $sortOptionsByField ?? null;
    }

    private function addProductQuantitiesField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        $showProductQuantitiesField = $fieldsetDisplayProperties->addField(
            'show_product_quantities',
            'select',
            [
                'name' => 'show_product_quantities',
                'label' => __('Show Product Quantities'),
                'title' => __('Show Product Quantities'),
                'values' => $this->showProductQuantities->toOptionArray(),
            ]
        );

        $dependence->addFieldMap(
            $showProductQuantitiesField->getHtmlId(),
            $showProductQuantitiesField->getName()
        );

        if ($displayModeField) {
            $dependence->addFieldDependence(
                $showProductQuantitiesField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ';',
                            'value' => implode(";", $this->displayMode->getShowProductQuantitiesConfig()),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }

        return $showProductQuantitiesField ?? null;
    }

    private function addSearchBoxField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField,
        $displayModeDependence
    ): ?AbstractElement {
        $showSearchBoxField = $fieldsetDisplayProperties->addField(
            'is_show_search_box',
            'select',
            [
                'name' => 'is_show_search_box',
                'label' => __('Show Search Box'),
                'title' => __('Show Search Box'),
                'values' => $this->yesNo->toOptionArray(),
            ]
        );

        $dependence->addFieldMap(
            $showSearchBoxField->getHtmlId(),
            $showSearchBoxField->getName()
        );

        if ($displayModeField) {
            $dependence->addFieldDependence(
                $showSearchBoxField->getName(),
                $displayModeField->getName(),
                $displayModeDependence
            );
        }

        return $showSearchBoxField ?? null;
    }

    private function addLimitOptionsField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField,
        ?AbstractElement $showSearchBoxField,
        $displayModeDependence
    ): ?AbstractElement {
        $showSearchBoxFieldIfManyOptions = $fieldsetDisplayProperties->addField(
            'limit_options_show_search_box',
            'text',
            [
                'name' => 'limit_options_show_search_box',
                'label' => __('Show the searchbox if the number of options more than'),
                'title' => __('Show the searchbox if the number of options more than'),
                'note' => __(
                    'Customers will be able to search for the filter option in the searchbox.'
                )
            ]
        );

        $dependence->addFieldMap(
            $showSearchBoxFieldIfManyOptions->getHtmlId(),
            $showSearchBoxFieldIfManyOptions->getName()
        );

        $dependence->addFieldDependence(
            $showSearchBoxFieldIfManyOptions->getName(),
            $showSearchBoxField->getName(),
            self::YES_NO_POSITIVE_VALUE
        );

        if ($displayModeField) {
            $dependence->addFieldDependence(
                $showSearchBoxFieldIfManyOptions->getName(),
                $displayModeField->getName(),
                $displayModeDependence
            );
        }

        return $showSearchBoxFieldIfManyOptions ?? null;
    }

    private function addNumberUnfoldedoptionsField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        $numberUnfoldedOptionsField = $fieldsetDisplayProperties->addField(
            'number_unfolded_options',
            'text',
            [
                'name' => 'number_unfolded_options',
                'label' => __('Number of Unfolded Options'),
                'title' => __('Number of Unfolded Options'),
                'note' => __('Other options will be shown after a customer clicks the "More" button.')
            ]
        );

        $dependence->addFieldMap(
            $numberUnfoldedOptionsField->getHtmlId(),
            $numberUnfoldedOptionsField->getName()
        );

        if ($displayModeField) {
            $dependence->addFieldDependence(
                $numberUnfoldedOptionsField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ';',
                            'value' => implode(";", $this->displayMode->getNumberUnfoldedOptionsConfig()),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }

        return $numberUnfoldedOptionsField ?? null;
    }

    private function addIsExpandedField(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence,
        ?AbstractElement $blockPosition
    ): ?AbstractElement {
        $isExpand = $fieldsetDisplayProperties->addField(
            'is_expanded',
            'select',
            [
                'name' => 'is_expanded',
                'label' => __('Expand'),
                'title' => __('Expand'),
                'values' => $this->expandSource->toOptionArray(),
                'note' => __('Allows to expand filter automatically right after a page is loaded.
                Set \'Expand for desktop only\' to keep filter minimized on mobile. Keep \'Auto\' to work
                based on the custom theme functionality.')
            ]
        );

        $dependence->addFieldMap(
            $isExpand->getHtmlId(),
            $isExpand->getName()
        );

        $dependence->addFieldDependence(
            $isExpand->getName(),
            $blockPosition->getName(),
            $this->dependencyFieldFactory->create(
                [
                    'fieldData' => [
                        'separator' => ';',
                        'value' => FilterPlacedBlock::POSITION_SIDEBAR . ';' . FilterPlacedBlock::POSITION_BOTH,
                        'negative' => false,
                    ],
                    'fieldPrefix' => ''
                ]
            )
        );

        return $isExpand ?? null;
    }

    private function addIsMultiselectField(
        Fieldset $fieldsetFiltering,
        Dependence $dependence,
        ?AbstractElement $displayModeField
    ): ?AbstractElement {
        if ($this->attributeObject->getAttributeCode() == Category::ATTRIBUTE_CODE) {
            $multiselectNote = __(
                'When multiselect option is disabled it follows the category page '
                . '(except the filtering from the search page)'
            );
        } else {
            $multiselectNote = null;
        }

        $multiselectField = $fieldsetFiltering->addField(
            'is_multiselect',
            'select',
            [
                'name' => 'is_multiselect',
                'label' => __('Allow Multiselect'),
                'title' => __('Allow Multiselect'),
                'values' => $this->yesNo->toOptionArray(),
                'note' => $multiselectNote,
            ]
        );
        $dependence->addFieldMap(
            $multiselectField->getHtmlId(),
            $multiselectField->getName()
        );
        if ($displayModeField) {
            $dependence->addFieldDependence(
                $multiselectField->getName(),
                $displayModeField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ';',
                            'value' => implode(";", $this->displayMode->getIsMultiselectConfig()),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }

        return $multiselectField ?? null;
    }

    private function addTooltipField(
        Fieldset $fieldsetDisplayProperties
    ): ?AbstractElement {
        $toolTip = $fieldsetDisplayProperties->addField(
            'tooltip',
            'text',
            [
                'name' => 'tooltip',
                'label' => __('Tooltip'),
                'title' => __('Tooltip'),
            ]
        );

        $toolTip->setRenderer(
            $this->getLayout()->createBlock(MultiStore::class)
                ->setName('tooltip')
        );

        return $toolTip ?? null;
    }

    private function addIsUseAndLogicField(
        Fieldset $fieldsetFiltering,
        Dependence $dependence,
        ?AbstractElement $multiselectField
    ): ?AbstractElement {
        if ($this->attributeObject->getAttributeCode() != Category::ATTRIBUTE_CODE) {
            $useAndLogicField = $fieldsetFiltering->addField(
                'is_use_and_logic',
                'select',
                [
                    'name' => 'is_use_and_logic',
                    'label' => __('Multiple Values Logic'),
                    'title' => __('Multiple Values Logic'),
                    'values' => $this->multipleValuesLogic->toOptionArray(),
                ]
            );

            $dependence->addFieldMap(
                $useAndLogicField->getHtmlId(),
                $useAndLogicField->getName()
            )->addFieldDependence(
                $useAndLogicField->getName(),
                $multiselectField->getName(),
                $this->dependencyFieldFactory->create(
                    [
                        'fieldData' => [
                            'separator' => ';',
                            'value' => implode(";", $this->displayMode->getIsMultiselectConfig()),
                            'negative' => false
                        ],
                        'fieldPrefix' => ''
                    ]
                )
            );
        }

        return $useAndLogicField ?? null;
    }

    private function addIsShowIconsField(
        Fieldset $fieldsetDisplayProperties
    ): ?AbstractElement {
        if ($this->attributeObject->getAttributeCode() != Category::ATTRIBUTE_CODE
            && $this->attributeObject->getFrontendInput() != 'price'
            && $this->configProvider->getBrandAttributeCode() != $this->attributeObject->getAttributeCode()
        ) {
            $isShowIcons = $fieldsetDisplayProperties->addField(
                'show_icons_on_product',
                'select',
                [
                    'name' => 'show_icons_on_product',
                    'label' => __('Show Icon on the Product Page'),
                    'title' => __('Show Icon on the Product Page'),
                    'note' => __('Upload images for your options to show them right after the product title'),
                    'values' => $this->yesNo->toOptionArray(),
                ]
            );
        }

        return $isShowIcons ?? null;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $this->prepareFilterSetting();
        $form->setDataObject($this->setting);

        $form->addField(
            'filter_code',
            'hidden',
            [
                'name' => 'filter_code',
                'value' => $this->setting->getFilterCode(),
            ]
        );

        /** @var  $dependence Dependence */
        $dependence = $this->getLayout()->createBlock(Dependence::class);

        $fieldsetDisplayProperties = $form->addFieldset(
            'shopby_fieldset_display_properties',
            ['legend' => __('Display Properties'), 'collapsable' => $this->getRequest()->has('popup')]
        );

        $displayModeField = $this->addDisplayModeField($fieldsetDisplayProperties, $dependence);
        $this->addFromToField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addMinSliderField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addMaxSliderField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addStepSliderField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addPriceFields($fieldsetDisplayProperties, $dependence, $displayModeField);
        $blockPosition = $this->addBlockPositionField($fieldsetDisplayProperties, $dependence);
        $this->addSortOptionsField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addProductQuantitiesField($fieldsetDisplayProperties, $dependence, $displayModeField);

        $displayModeDependence = $this->dependencyFieldFactory->create(
            [
                'fieldData' => [
                    'separator' => ';',
                    'value' => DisplayMode::MODE_DEFAULT . ';' . DisplayMode::MODE_IMAGES_LABELS,
                    'negative' => false,
                ],
                'fieldPrefix' => ''
            ]
        );

        $showSearchBoxField = $this->addSearchBoxField(
            $fieldsetDisplayProperties,
            $dependence,
            $displayModeField,
            $displayModeDependence
        );
        $this->addLimitOptionsField(
            $fieldsetDisplayProperties,
            $dependence,
            $displayModeField,
            $showSearchBoxField,
            $displayModeDependence
        );
        $this->addNumberUnfoldedoptionsField($fieldsetDisplayProperties, $dependence, $displayModeField);
        $this->addIsExpandedField($fieldsetDisplayProperties, $dependence, $blockPosition);
        $this->addTooltipField($fieldsetDisplayProperties);

        $this->addCategoriesVisibleFilter($fieldsetDisplayProperties, $dependence);
        if ($this->attributeObject->getAttributeCode() == Category::ATTRIBUTE_CODE) {
            $this->addCategorySettingFields($fieldsetDisplayProperties, $dependence);
        }

        $fieldsetFiltering = $form->addFieldset(
            'shopby_fieldset_filtering',
            ['legend' => __('Filtering'), 'collapsable' => $this->getRequest()->has('popup')]
        );
        $dependence->addFieldsets(
            $fieldsetFiltering->getHtmlId(),
            self::FIELD_FRONTEND_INPUT,
            ['value' => 'price', 'negative' => false]
        );

        $multiselectField = $this->addIsMultiselectField($fieldsetFiltering, $dependence, $displayModeField);
        $this->addIsUseAndLogicField($fieldsetFiltering, $dependence, $multiselectField);
        $this->addIsShowIconsField($fieldsetDisplayProperties);
        $this->modifyFormData($dependence, $form);

        return parent::_prepareForm();
    }

    private function modifyFormData(Dependence $dependence, Form $form)
    {
        $this->setChild(
            'form_after',
            $dependence
        );

        $this->_eventManager->dispatch(
            'amshopby_attribute_form_tab_build_after',
            ['form' => $form, 'setting' => $this->setting, 'dependence' => $dependence]
        );

        $this->setForm($form);
        $data = $this->setting->getData();

        if (isset($data['slider_step'])) {
            $data['slider_step'] = round($data['slider_step'], 4);
        }

        if (!isset($data[FilterSettingInterface::TOP_POSITION])) {
            $data[FilterSettingInterface::TOP_POSITION] = 0;
        }
        if (!isset($data[FilterSettingInterface::SIDE_POSITION])) {
            $data[FilterSettingInterface::SIDE_POSITION] = 0;
        }

        $data[self::DISPLAY_MODE] = $data[self::DISPLAY_MODE] ?? $this->getPreselectDisplayMode();

        $form->setValues($data);
    }

    /**
     * @param Fieldset $fieldsetDisplayProperties
     * @param Dependence $dependence
     * @return Fieldset
     */
    protected function addCategoriesVisibleFilter(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence
    ) {
        $fieldsetDisplayProperties->addFieldset(
            'shopby_fieldset_visibility',
            ['legend' => __('Visibility'), 'collapsable' => $this->getRequest()->has('popup')]
        );

        $visibleInCategories = $fieldsetDisplayProperties->addField(
            'visible_in_categories',
            'select',
            [
                'name' => 'visible_in_categories',
                'label' => __('Visible in Categories'),
                'title' => __('Visible in Categories'),
                'values' => $this->visibleInCategory->toOptionArray(),
            ]
        );

        $this->addDependentFiltersFilter($fieldsetDisplayProperties);

        $categoryFilter = $fieldsetDisplayProperties->addField(
            'categories_filter',
            'multiselect',
            [
                'name' => 'categories_filter',
                'label' => __('Categories'),
                'title' => __('Categories'),
                'values' => $this->categorySource->toOptionArray(),
            ]
        );

        $dependence->addFieldMap(
            $visibleInCategories->getHtmlId(),
            $visibleInCategories->getName()
        )->addFieldMap(
            $categoryFilter->getHtmlId(),
            $categoryFilter->getName()
        )->addFieldDependence(
            $categoryFilter->getName(),
            $visibleInCategories->getName(),
            $this->dependencyFieldFactory->create(
                [
                    'fieldData' => ['value' => (string)VisibleInCategory::VISIBLE_EVERYWHERE, 'negative' => true],
                    'fieldPrefix' => ''
                ]
            )
        );

        return $fieldsetDisplayProperties;
    }

    /**
     * @param Fieldset $fieldsetDisplayProperties
     * @return Fieldset
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addDependentFiltersFilter(Fieldset $fieldsetDisplayProperties)
    {
        $attributesFilter = $fieldsetDisplayProperties->addField(
            'attributes_filter',
            'multiselect',
            [
                'name' => 'attributes_filter',
                'label' => __('Show Only when Any Option of Attributes Below is Selected'),
                'title' => __('Show Only when Any Option of Attributes Below is Selected'),
                'values' => $this->attributeSource->toOptionArray(),
            ]
        );

        /** @var Multiselect $multiselectRenderer */
        $multiselectRenderer = $this->getLayout()
            ->createBlock(Multiselect::class);
        $attributesFilter->setRenderer($multiselectRenderer);

        $attributeOptions = $this->attributeOptionSource->toOptionArray();
        if (count($attributeOptions) < self::MAX_ATTRIBUTE_OPTIONS_COUNT) {
            $attributesOptionsFilter = $fieldsetDisplayProperties->addField(
                'attributes_options_filter',
                'multiselect',
                [
                    'name' => 'attributes_options_filter',
                    'label' => __('Show Only if the Following Option is Selected'),
                    'title' => __('Show Only if the Following Option is Selected'),
                    'values' => $attributeOptions
                ]
            );

            /** @var Multiselect $multiselectRenderer */
            $multiselectRenderer = $this->getLayout()
                ->createBlock(Multiselect::class);
            $attributesOptionsFilter->setRenderer($multiselectRenderer);
        } else {
            $attributesOptionsFilter = $fieldsetDisplayProperties->addField(
                'attributes_options_filter',
                'text',
                [
                    'name' => 'attributes_options_filter',
                    'label' => __('Show Only if the Following Option is Selected'),
                    'title' => __('Show Only if the Following Option is Selected'),
                    'note' => __('Comma separated options ids')
                ]
            );

            $attributesOptionsSetting = $this->setting->getAttributesOptionsFilter();
            $this->setting->setAttributesOptionsFilter(implode(',', $attributesOptionsSetting));
        }

        return $fieldsetDisplayProperties;
    }

    /**
     * @param Fieldset $fieldsetDisplayProperties
     * @param Dependence $dependence
     */
    protected function addCategorySettingFields(
        Fieldset $fieldsetDisplayProperties,
        Dependence $dependence
    ) {
        $fieldsetDisplayProperties->addFieldset(
            'shopby_fieldset_categories_tree',
            ['legend' => __('Render Categories Tree'), 'collapsable' => $this->getRequest()->has('popup')]
        );

        $linkToGuide = 'https://amasty.com/docs/doku.php?id=magento_2:'
                    . 'improved_layered_navigation&utm_source=extension&utm_medium=backend&utm_campaign='
                    . 'userguide_Amasty_Shopby#category_tree';

        // @codingStandardsIgnoreStart
        $fieldsetDisplayProperties->addField(
            'customer_help',
            'label',
            [
                'name' => 'customer_help',
                'note' => __(
                    'Need help with the settings? Please consult the '
                    . '<a href="%1">user guide</a> to configure the extension properly.',
                    $linkToGuide
                ),
            ]
        );
        // @codingStandardsIgnoreEnd

        $renderAllCategoriesTreeField = $fieldsetDisplayProperties->addField(
            'render_all_categories_tree',
            'select',
            [
                'name' => 'render_all_categories_tree',
                'label' => __('Render All Categories Tree'),
                'title' => __('Render All Categories Tree'),
                'values' => $this->renderCategoriesTreeSource->toOptionArray(),
                'note' => __('Yes (Render Full Categories Tree) or No (For category filter tree customization)')
            ]
        );
        $renderAllCategoriesTreeFieldValues = ',1';
        $categoryTreeDepthField = $fieldsetDisplayProperties->addField(
            'category_tree_depth',
            'text',
            [
                'name' => 'category_tree_depth',
                'label' => __('Category Tree Depth'),
                'title' => __('Category Tree Depth'),
                'class' => 'validate-greater-than-zero',
                'note' => __('Specify the max level number for category tree. Keep 1 to hide the subcategories'),
            ]
        );

        $renderCategoriesLevelField = $fieldsetDisplayProperties->addField(
            'render_categories_level',
            'select',
            [
                'name' => 'render_categories_level',
                'label' => __('Render Categories Level'),
                'title' => __('Render Categories Level'),
                'values' => $this->renderCategoriesLevelSource->toOptionArray(),
            ]
        );

        $categoryTreeDepthFieldValues = ',0,1';

        $dependence->addFieldMap(
            $categoryTreeDepthField->getHtmlId(),
            $categoryTreeDepthField->getName()
        );

        $subcategoriesViewField = $fieldsetDisplayProperties->addField(
            'subcategories_view',
            'select',
            [
                'name' => 'subcategories_view',
                'label' => __('Subcategories View'),
                'title' => __('Subcategories View'),
                'values' => $this->subcategoriesViewSource->toOptionArray()
            ]
        );

        $dependence->addFieldMap(
            $subcategoriesViewField->getHtmlId(),
            $subcategoriesViewField->getName()
        );

        $categoryTreeDisplayMode = $fieldsetDisplayProperties->addField(
            'category_tree_display_mode',
            'select',
            [
                'name' => 'category_tree_display_mode',
                'label' => __('Category Tree Display Mode'),
                'title' => __('Category Tree Display Mode'),
                'values' => $this->categoryTreeDisplayMode->toOptionArray(),
            ]
        );

        $dependence->addFieldMap(
            $categoryTreeDisplayMode->getHtmlId(),
            $categoryTreeDisplayMode->getName()
        );

        $subcategoriesExpandField = $fieldsetDisplayProperties->addField(
            'subcategories_expand',
            'select',
            [
                'name' => 'subcategories_expand',
                'label' => __('Expand Subcategories'),
                'title' => __('Expand Subcategories'),
                'values' => $this->subcategoriesExpandSource->toOptionArray()
            ]
        );

        $dependence->addFieldMap(
            $subcategoriesExpandField->getHtmlId(),
            $subcategoriesExpandField->getName()
        )->addFieldDependence(
            $subcategoriesExpandField->getName(),
            $subcategoriesViewField->getName(),
            (string)SubcategoriesView::FOLDING
        );

        $dependence->addFieldMap(
            $renderAllCategoriesTreeField->getHtmlId(),
            $renderAllCategoriesTreeField->getName()
        )->addFieldMap(
            $renderCategoriesLevelField->getHtmlId(),
            $renderCategoriesLevelField->getName()
        )->addFieldDependence(
            $renderCategoriesLevelField->getName(),
            $categoryTreeDepthField->getName(),
            $this->dependencyFieldFactory->create(
                [
                    'fieldData' => ['value' => $categoryTreeDepthFieldValues, 'separator' => ',', 'negative' => true],
                    'fieldPrefix' => ''
                ]
            )
        )->addFieldDependence(
            $categoryTreeDepthField->getName(),
            $renderAllCategoriesTreeField->getName(),
            $this->dependencyFieldFactory->create(
                [
                    'fieldData' => [
                        'value' => $renderAllCategoriesTreeFieldValues,
                        'separator' => ',',
                        'negative' => true
                    ],
                    'fieldPrefix' => ''
                ]
            )
        )->addFieldDependence(
            $renderCategoriesLevelField->getName(),
            $renderAllCategoriesTreeField->getName(),
            $this->dependencyFieldFactory->create(
                [
                    'fieldData' => [
                        'value' => $renderAllCategoriesTreeFieldValues,
                        'separator' => ',',
                        'negative' => true
                    ],
                    'fieldPrefix' => ''
                ]
            )
        );
    }

    protected function prepareFilterSetting()
    {
        if ($this->attributeObject->getId()) {
            $filterCode = FilterSettingHelper::ATTR_PREFIX . $this->attributeObject->getAttributeCode();
            $this->setting = $this->filterSettingHelper
                ->getSettingByAttributeCode($this->attributeObject->getAttributeCode());
            if (!$this->setting->getId()) {
                $this->setting->setRelNofollow(RelNofollow::MODE_AUTO);
            }
            $this->setting->setFilterCode($filterCode);
            if ($filterCode == FilterSettingHelper::ATTR_PREFIX . Category::ATTRIBUTE_CODE) {
                $this->setting->addData($this->filterSettingHelper->getCustomDataForCategoryFilter());
            }
            if (!$this->isNeedDisplayMode()
                || $this->setting->getData(self::DISPLAY_MODE) == DisplayMode::MODE_DROPDOWN
            ) {
                $this->setting->setDisplayMode(DisplayMode::MODE_DEFAULT);
            }
        }
    }

    /**
     * @return string
     */
    private function getPreselectDisplayMode()
    {
        $preselectValue = '';
        if ($this->attributeObject->getFrontendInput() === 'select' && $this->attributeObject->getAdditionalData()) {
            $additionalData = $this->serializer->unserialize($this->attributeObject->getAdditionalData());
            $frontendInput = $additionalData[Swatch::SWATCH_INPUT_TYPE_KEY] ?? '';
            $preselectValue = DisplayMode::DISPLAY_MODE_FRONTEND_INPUT_MAP[$frontendInput] ?? '';
        }

        return $preselectValue;
    }

    private function isNeedDisplayMode(): bool
    {
        return !in_array(
            $this->attributeObject->getFrontendInput(),
            [
                'text',
                'textarea',
                'texteditor',
                'pagebuilder',
                'date',
                'datetime',
                'boolean',
                'media_image',
                'weee'
            ]
        );
    }
}
