<?php

namespace Amasty\ShopbySeo\Observer\Admin;

use Amasty\Shopby\Block\Adminhtml\Form\Renderer\Fieldset\MultiStore;
use Amasty\Shopby\Helper\Category;
use Amasty\ShopbyBase\Block\Widget\Form\Element\Dependence;
use Amasty\ShopbySeo\Model\Source\GenerateSeoUrl;
use Amasty\ShopbySeo\Model\Source\IndexMode;
use Amasty\ShopbySeo\Model\Source\RelNofollow;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Amasty\ShopbyBase\Helper\Data as BaseHelper;
use Magento\Framework\View\LayoutInterface;

class AttributeFormTabBuildAfter implements ObserverInterface
{
    /**
     * @var  GenerateSeoUrl
     */
    protected $generateSeoUrl;

    /**
     * @var  IndexMode
     */
    protected $indexMode;

    /**
     * @var  Attribute
     */
    protected $attribute;

    /**
     * @var RelNofollow
     */
    protected $relNofollow;

    /**
     * @var BaseHelper
     */
    private $baseHelper;

    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(
        GenerateSeoUrl $generateSeoUrl,
        BaseHelper $baseHelper,
        IndexMode $indexMode,
        RelNofollow $relNofollow,
        Registry $registry,
        LayoutInterface $layout
    ) {
        $this->generateSeoUrl = $generateSeoUrl;
        $this->indexMode = $indexMode;
        $this->relNofollow = $relNofollow;
        $this->attribute = $registry->registry('entity_attribute');
        $this->baseHelper = $baseHelper;
        $this->layout = $layout;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Form $form */
        $form = $observer->getData('form');

        $fieldset = $form->addFieldset(
            'shopby_fieldset_seo',
            ['legend' => __('SEO')]
        );

        $this->addGenerateSeoUrlField($fieldset);
        $this->addIndexModeField($fieldset);
        $this->addFollowModeField($fieldset);
        $this->addRelNofollowField($fieldset);
        $this->addAttributeUrlAliasField($fieldset);
        $this->addCategoryFieldset($fieldset, $observer->getData('dependence'));
    }

    private function addGenerateSeoUrlField(Fieldset $fieldset): void
    {
        if ($this->isCanApplySeoConfig()) {
            $fieldset->addField(
                'is_seo_significant',
                'select',
                [
                    'name'   => 'is_seo_significant',
                    'label'  => __('Generate SEO URL'),
                    'title'  => __('Generate SEO URL'),
                    'note'  => $this->getSeoUrlNote(),
                    'values' => $this->generateSeoUrl->toOptionArray(),
                ]
            );
        }
    }

    /**
     * @return Phrase|string
     */
    private function getSeoUrlNote()
    {
        if ($this->baseHelper->getBrandAttributeCode() == $this->attribute->getAttributeCode()) {
            $note = __('SEO URL is always generated for the brand.');
        }

        return $note ?? '';
    }

    private function addIndexModeField(Fieldset $fieldset): void
    {
        $fieldset->addField(
            'index_mode',
            'select',
            [
                'name'     => 'index_mode',
                'label'    => __('Allow Google to INDEX the Category Page with the Filter Applied'),
                'title'    => __('Allow Google to INDEX the Category Page with the Filter Applied'),
                'values'   => $this->indexMode->toOptionArray(),
            ]
        );
    }

    private function addFollowModeField(Fieldset $fieldset): void
    {
        $fieldset->addField(
            'follow_mode',
            'select',
            [
                'name'     => 'follow_mode',
                'label'    => __('Allow Google to FOLLOW Links on the Category Page with the Filter Applied'),
                'title'    => __('Allow Google to FOLLOW Links on the Category Page with the Filter Applied'),
                'values'   => $this->indexMode->toOptionArray(),
            ]
        );
    }

    private function addRelNofollowField(Fieldset $fieldset): void
    {
        $fieldset->addField(
            'rel_nofollow',
            'select',
            [
                'name'     => 'rel_nofollow',
                'label'    => __('Add rel=\'nofollow\' to Filter Links'),
                'title'    => __('Add rel=\'nofollow\' to filter links'),
                'values'   => $this->relNofollow->toOptionArray(),
            ]
        );
    }

    private function addCategoryFieldset(Fieldset $fieldset, Dependence $dependence): void
    {
        if ($this->attribute->getAttributeCode() == Category::ATTRIBUTE_CODE) {
            $dependence->addFieldsets(
                $fieldset->getHtmlId(),
                'is_multiselect',
                ['value' => '0', 'negative' => false]
            );
        }
    }

    private function addAttributeUrlAliasField(Fieldset $fieldset): void
    {
        if ($this->isCanApplySeoConfig()) {
            $attributeUrlAlias = $fieldset->addField(
                'attribute_url_alias',
                'text',
                [
                    'name' => 'attribute_url_alias',
                    'label' => __('Attribute URL Alias'),
                    'title' => __('Attribute URL Alias'),
                    'note' => __('If left empty, Attribute Code value will be used.')
                ]
            );

            $attributeUrlAlias->setRenderer(
                $this->layout->createBlock(MultiStore::class)->setName('attribute_url_alias')
            );
        }
    }

    private function isCanApplySeoConfig(): bool
    {
        return $this->attribute->getAttributeCode() != Category::ATTRIBUTE_CODE
            && $this->attribute->getFrontendInput() != 'price';
    }
}
