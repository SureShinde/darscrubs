<?php

declare(strict_types=1);

namespace Amasty\Shopby\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement as AbstractElement;

class ExcludeOutOfStock extends Field
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        Configuration $configuration,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configuration = $configuration;
    }

    public function render(AbstractElement $element)
    {
        return $this->configuration->isShowOutOfStock() ? parent::render($element) : '';
    }
}
