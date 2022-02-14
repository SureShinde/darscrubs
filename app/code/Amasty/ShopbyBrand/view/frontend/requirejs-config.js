var config = {
    map: {
        '*': {
            amBrandsSearch: 'Amasty_ShopbyBrand/js/components/ambrands-search',
            amBrandsFilterInit: 'Amasty_ShopbyBrand/js/components/ambrands-filter-init',
            amBrandsTooltipInit: 'Amasty_ShopbyBrand/js/components/ambrands-tooltip-init',
            amBrandsFilter: 'Amasty_ShopbyBrand/js/brand-filter'
        }
    },
    paths: {
        'swiper': 'Amasty_ShopbyBase/js/swiper.min',
    },
    shim: {
        'swiper': {
            deps: ['jquery']
        }
    },
    config: {
        mixins: {
            'mage/menu': {
                'Amasty_ShopbyBrand/js/lib/mage/ambrands-menu-mixin': true
            }
        }
    }
};
