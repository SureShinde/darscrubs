<?php

namespace Amasty\Shopby\Block\Navigation;

use Amasty\Shopby\Model\UrlResolver\UrlResolverInterface;
use Magento\Framework\View\Element\Template;

/**
 * @api
 */
class JsInit extends \Magento\Framework\View\Element\Template
{
    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'jsinit.phtml';

    /**
     * @var \Amasty\Shopby\Helper\Data
     */
    private $helper;

    /**
     * @var UrlResolverInterface
     */
    private $urlResolver;

    public function __construct(
        Template\Context $context,
        \Amasty\Shopby\Helper\Data $helper,
        UrlResolverInterface $urlResolver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->urlResolver = $urlResolver;
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function collectFilters()
    {
        return (int)$this->helper->collectFilters();
    }

    /**
     * @return string
     */
    public function getClearUrl(): string
    {
        return $this->urlResolver->resolve();
    }
}
