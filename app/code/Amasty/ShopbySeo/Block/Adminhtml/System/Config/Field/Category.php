<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Block\Adminhtml\System\Config\Field;

use Amasty\Base\Model\ModuleInfoProvider;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement as AbstractElement;

class Category extends \Magento\Config\Block\System\Config\Form\Field
{
    // phpcs:ignore
    const GUIDE_LINK = 'https://amasty.com/docs/doku.php?id=magento_2:improved_layered_navigation&utm_source=extension&utm_medium=link&utm_campaign=iln_canonical_url_settings_m2';

    const MARKET_GUIDE_LINK = 'https://marketplace.magento.com/amasty-shopby.html';

    /**
     * @var ModuleInfoProvider
     */
    private $moduleInfoProvider;

    public function __construct(
        Context $context,
        ModuleInfoProvider $moduleInfoProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleInfoProvider = $moduleInfoProvider;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $link = $this->moduleInfoProvider->isOriginMarketplace() ? self::MARKET_GUIDE_LINK : self::GUIDE_LINK;

        $element->setComment(
            __(
                'Set the structure of canonical urls for category pages. Need help with the setting?'
                . ' Please consult the <a target="_blank" href="%1">user guide</a> to configure properly.',
                $link
            )
        );

        return parent::render($element);
    }
}
