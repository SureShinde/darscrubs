<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Swatches\Helper\Media as MediaHelper;

class Visual extends \Magento\Ui\Component\Form\Field
{
    /**
     * @var MediaHelper
     */
    private $mediaHelper;

    public function __construct(
        MediaHelper $mediaHelper,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->mediaHelper = $mediaHelper;
    }

    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        $config['swatchPath'] = $this->mediaHelper->getSwatchMediaUrl();
        $this->setData('config', $config);
    }
}
