<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\Component\Form;

use Amasty\GroupedOptions\Model\Source\GroupForm\AttributeOption as AttributeOptionSource;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class AttributeOptions extends \Magento\Ui\Component\Form\Element\CheckboxSet
{
    /**
     * @var AttributeOptionSource
     */
    private $attributeOptionSource;

    public function __construct(
        AttributeOptionSource $attributeOptionSource,
        ContextInterface $context,
        $options = null,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $options, $components, $data);
        $this->attributeOptionSource = $attributeOptionSource;
    }

    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        $config['attributeOptionsData'] = $this->attributeOptionSource->toOptionArray();
        $this->setData('config', $config);
    }
}
