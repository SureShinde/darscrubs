<?php

namespace Amasty\Shopby\Block\Navigation\Top;

class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    const PRODUCT_LISTING_SEARCH_BLOCK = 'search.result';
    const PRODUCT_LISTING_TOOLBAR_BLOCK = 'product_list_toolbar';

    /**
     * @return \Magento\LayeredNavigation\Block\Navigation
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeToHtml()
    {
        $productListingBlock = $this->getLayout()->getBlock(self::PRODUCT_LISTING_SEARCH_BLOCK);
        if ($productListingBlock) {
            $toolbarBlock = $this->getLayout()->getBlock(self::PRODUCT_LISTING_TOOLBAR_BLOCK);
            if ($toolbarBlock) {

                $toolbarBlock->setData('_current_grid_order', null);
                $toolbarBlock->setData('_current_grid_direction', null);

                $orders = $toolbarBlock->getAvailableOrders();
                unset($orders['position']);
                $orders['relevance'] = __('Relevance');
                $toolbarBlock->setAvailableOrders(
                    $orders
                )->setDefaultDirection(
                    'desc'
                )->setDefaultOrder(
                    'relevance'
                );
            }
        }

        return parent::_beforeToHtml();
    }
}
