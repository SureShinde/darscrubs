<?php

declare(strict_types=1);

namespace Amasty\ShopbySeo\Plugin\Framework\Controller;

use Amasty\ShopbySeo\Helper\Config;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

/**
 * Find last page only after rendering product list block.
 */
class ProcessPageResultPlugin
{
    const PREV_NEXT_LINK_REGEX = '/link rel="(prev|next)"/';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    public function __construct(
        Config $config,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->config = $config;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
        $this->layout = $layout;
    }

    public function afterRenderResult(
        ResultInterface $subject,
        ResultInterface $result,
        ResponseInterface $response
    ): ResultInterface {
        $output = $response->getBody();

        if ($this->config->getModuleConfig('other/prev_next')
            && $subject instanceof \Magento\Framework\View\Result\Page
            && !preg_match(self::PREV_NEXT_LINK_REGEX, $output)
        ) {
            $output = $this->modifyBody($output);
            $response->setBody($output);
        }

        return $result;
    }

    public function modifyBody(string $output): string
    {
        $html = $this->getPrevNextLinkContent();

        if ($html) {
            $head = '</head>';
            $output = str_replace($head, $html . $head, $output);
        }

        return $output;
    }

    public function getPrevNextLinkContent(): string
    {
        $html = '';
        $productListBlock = $this->getCategoryProductListBlock();

        if ($productListBlock) {
            $toolbarBlock = $productListBlock->getToolbarBlock();
            /** @var \Magento\Theme\Block\Html\Pager $pagerBlock */
            $pagerBlock = $toolbarBlock->getChildBlock('product_list_toolbar_pager');

            if ($pagerBlock) {
                $pagerBlock
                    ->setLimit($toolbarBlock->getLimit())
                    ->setAvailableLimit($toolbarBlock->getAvailableLimit())
                    ->setCollection($productListBlock->getLayer()->getProductCollection());
                $lastPage = $pagerBlock->getLastPageNum();
                $currentPage = $pagerBlock->getCurrentPage();

                if ($currentPage > 1) {
                    $url = $this->getPageUrl($pagerBlock->getPageVarName(), $currentPage - 1);
                    $html .= sprintf($this->getLinkTemplate(), 'prev', $url);
                }

                if ($currentPage < $lastPage) {
                    $url = $this->getPageUrl($pagerBlock->getPageVarName(), $currentPage + 1);
                    $html .= sprintf($this->getLinkTemplate(), 'next', $url);
                }
            }
        }

        return $html;
    }

    /**
     * @return \Magento\Catalog\Block\Product\ListProduct
     */
    private function getCategoryProductListBlock()
    {
        $productListBlock = $this->layout->getBlock('category.products.list');

        if (!$productListBlock) {
            foreach ($this->layout->getAllBlocks() as $block) {
                if ($block instanceof ListProduct) {
                    $productListBlock = $block;
                    break;
                }
            }
        }

        return $productListBlock;
    }

    private function getPageUrl(string $key, int $value): string
    {
        $currentUrl = $this->urlBuilder->getCurrentUrl();
        $currentUrl = $this->escaper->escapeUrl($currentUrl);
        $result = preg_replace('/(\W)' . $key . '=\d+/', "$1$key=$value", $currentUrl, -1, $count);

        if ($value == 1) {
            $result = str_replace($key . '=1&amp;', '', $result); //not last & not single param
            $result = str_replace('&amp;' . $key . '=1', '', $result); //last param
            $result = str_replace('?' . $key . '=1', '', $result); //single param
        } elseif (!$count) {
            $delimiter = (strpos($currentUrl, '?') === false) ? '?' : '&amp;';
            $result .= $delimiter . $key . '=' . $value;
        }

        return $result;
    }

    private function getLinkTemplate(): string
    {
        return '<link rel="%s" href="%s" />' . PHP_EOL;
    }
}
