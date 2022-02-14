/**
 * @author    Amasty Team
 * @copyright Copyright (c) Amasty Ltd. ( http://www.amasty.com/ )
 * @package   Amasty_Shopby
 */
define([
    "jquery",
    "knockout",
    'amShopbyTopFilters',
    "productListToolbarForm",
    "amShopbyFilterAbstract",
    "Magento_PageCache/js/page-cache"
], function ($, ko, amShopbyTopFilters) {
    'use strict';
    $.widget('mage.amShopbyAjax', {
        prevCall: false,
        $shopbyOverlay: null,
        cached: [],
        memorizeData: [],
        blockToolbarProcessing: false,
        swatchesTooltip: '.swatch-option-tooltip',
        filterTooltip: '.amshopby-filter-tooltip',
        response: null,
        cacheKey: null,
        startAjax: false,
        isCategorySingleSelect: 0,
        nodes: {
            loader: $('<div id="amasty-shopby-overlay" class="amshopby-overlay-block">' +
                '<span class="amshopby-loader"></span></div>')
        },
        selectors: {
            products_wrapper: '#amasty-shopby-product-list, .search.results',
            top_nav: '.amasty-catalog-topnav',
            products: '.products.wrapper',
            overlay: '#amasty-shopby-overlay',
            top_navigation: '.catalog-topnav .block.filter',
            js_init: '[data-am-js="js-init"]',
            title_head: '#page-title-heading'
        },

        _create: function () {
            var self = this;
            $(function () {
                self.initWidget();

                if (typeof window.history.replaceState === "function") {
                    window.history.replaceState({url: document.URL}, document.title);

                    setTimeout(function () {
                        /*
                         Timeout is a workaround for iPhone
                         Reproduce scenario is following:
                         1. Open category
                         2. Use pagination
                         3. Click on product
                         4. Press "Back"
                         Result: Ajax loads the same content right after regular page load
                         */
                        window.onpopstate = function (e) {
                            if (e.state) {
                                if ($.mage.amShopbyApplyFilters) {
                                    $.mage.amShopbyApplyFilters.prototype.showButtonClick = true;
                                }
                                self.callAjax(e.state.url, []);
                                self.$shopbyOverlay.show();
                            }
                        };
                    }, 0)
                }
            });
        },

        initWidget: function () {
            var self = this,
                swatchesTooltip = $(self.swatchesTooltip),
                filterTooltip = $(self.filterTooltip);

            $(document).on('baseCategory', function (event, eventData) {
                self.currentCategoryId = eventData;
            });

            $(document).on('amshopby:submit_filters', function (event, eventData) {
                var data = eventData.data,
                    clearUrl = self.options.clearUrl,
                    isSorting = eventData.isSorting,
                    pushState = !self.options.submitByClick;

                if (typeof data.clearUrl !== 'undefined') {
                    clearUrl = data.clearUrl;
                    delete data.clearUrl;
                }

                if (self.prevCall) {
                    self.prevCall.abort();
                }

                var dataAndUrl = data.slice(0);

                dataAndUrl.push(clearUrl ? clearUrl : self.options.clearUrl);

                var cacheKey = JSON.stringify(dataAndUrl);
                $.mage.amShopbyAjax.prototype.cacheKey = cacheKey;
                if (self.cached[cacheKey]) {
                    var response = self.cached[cacheKey];
                    if (pushState || isSorting) {
                        if (response.newClearUrl
                            && response.newClearUrl.indexOf('?p=') == -1
                            && response.newClearUrl.indexOf('&p=') == -1
                        ) {
                            self.options.clearUrl = response.newClearUrl;
                        }

                        window.history.pushState({url: response.url}, '', response.url);
                        self.reloadHtml(response);
                        self.initAjax();
                    } else if ($.mage.amShopbyApplyFilters) {
                        $.mage.amShopbyApplyFilters.prototype.showButtonCounter(
                            response.productsCount
                        );
                    }

                    return;
                }

                self.prevCall = self.callAjax(clearUrl, data, pushState, cacheKey, isSorting);
            });

            $(document).on('touchstart touchmove scroll', function () {
                if (swatchesTooltip) {
                    swatchesTooltip.hide();
                    filterTooltip.trigger('mouseleave');
                }
            });

            $(document).on('amshopby:reload_html', function (event, eventData) {
                self.reloadHtml(eventData.response);
            });

            filterTooltip.on('tap', function () {
                $(this).trigger('mouseenter');
            });

            self.initAjax();
        },

        callAjax: function (clearUrl, data, pushState, cacheKey, isSorting) {
            var self = this;

            if (pushState || isSorting) {
                this.$shopbyOverlay.show();
            }

            data.every(function (item, key) {
                if (item.name.indexOf('[cat]') != -1) {
                    if (item.value == self.options.currentCategoryId) {
                        data.splice(key, 1);
                    } else {
                        item.value.split(',').filter(function (element) {
                            return element != self.options.currentCategoryId
                        }).join(',');
                    }

                    return false;
                }
            });

            data.push({name: 'shopbyAjax', value: 1});
            data = this.addMemorizeData(data);
            $.mage.amShopbyAjax.prototype.startAjax = true;

            if (!clearUrl) {
                clearUrl = self.options.clearUrl;
            }
            clearUrl = clearUrl.replace(/amp;/g, '');
            self.clearUrl = clearUrl;

            return $.ajax({
                url: clearUrl,
                data: data,
                cache: true,
                success: function (response) {
                    try {
                        $.mage.amShopbyAjax.prototype.startAjax = false;

                        response = $.parseJSON(response);

                        if (response.isDisplayModePage) {
                            throw new Error();
                        }

                        if (cacheKey) {
                            self.cached[cacheKey] = response;
                        }

                        $.mage.amShopbyAjax.prototype.response = response;
                        if (response.newClearUrl
                            && (response.newClearUrl.indexOf('?p=') == -1 && response.newClearUrl.indexOf('&p=') == -1)) {
                            self.options.clearUrl = response.newClearUrl;
                        }

                        if (pushState || ($.mage.amShopbyApplyFilters && $.mage.amShopbyApplyFilters.prototype.showButtonClick) || isSorting) {
                            window.history.pushState({url: response.url}, '', response.url);
                        }

                        if (self.options.submitByClick !== 1 || isSorting) {
                            self.reloadHtml(response);
                        }

                        if ($.mage.amShopbyApplyFilters && $.mage.amShopbyApplyFilters.prototype.showButtonClick) {
                            $.mage.amShopbyApplyFilters.prototype.showButtonClick = false;
                            $.mage.amShopbyAjax.prototype.response = false;
                            self.reloadHtml(response);
                        }

                        if ($.mage.amShopbyApplyFilters) {
                            $.mage.amShopbyApplyFilters.prototype.showButtonCounter(response.productsCount);
                        }

                        $(document).trigger('amshopby:ajax_filter_applied');
                    } catch (e) {
                        var url = self.clearUrl ? self.clearUrl : self.options.clearUrl;
                        window.location = (this.url.indexOf('shopbyAjax') == -1) ? this.url : url;
                    }
                },
                error: function (response) {
                    try {
                        if (response.getAllResponseHeaders() != '') {
                            self.options.clearUrl ? window.location = self.options.clearUrl : location.reload();
                        }
                    } catch (e) {
                        window.location = (this.url.indexOf('shopbyAjax') == -1) ? this.url : self.options.clearUrl;
                    }
                },
                always: function () {
                    if (self.$shopbyOverlay) {
                        self.$shopbyOverlay.hide();
                    }
                }
            });
        },

        addMemorizeData: function (data) {
            if (this.memorizeData) {
                $.each(this.memorizeData, function(key, param) {
                    data.push({name: param.name, value: param.value});
                });
            }

            return data;
        },

        reloadHtml: function (data) {
            var selectSidebarNavigation = '.sidebar.sidebar-main .block.filter',
                selectTopNavigation = selectSidebarNavigation + '.amshopby-all-top-filters-append-left',
                selectMainNavigation = '',
                $productsWrapper = this.getProductBlock();

            this.options.currentCategoryId = data.currentCategoryId
                ? data.currentCategoryId
                : this.options.currentCategoryId;

            if ($(selectTopNavigation).first().length > 0) {
                selectMainNavigation = selectTopNavigation; //if all filters are top
            } else if ($(selectSidebarNavigation).first().length > 0) {
                selectMainNavigation = selectSidebarNavigation;
            }

            $('.am_shopby_apply_filters').remove();
            if (!selectMainNavigation) {
                if ($('.sidebar.sidebar-main').first().length) {
                    $('.sidebar.sidebar-main').first().prepend("<div class='block filter'></div>");
                    selectMainNavigation = selectSidebarNavigation;
                } else {
                    selectMainNavigation = '.block.filter';
                }
            }

            $(selectMainNavigation).first().replaceWith(data.navigation);
            $(selectMainNavigation).first().trigger('contentUpdated');

            //top nav already exist into categoryProducts
            if (!data.categoryProducts || data.categoryProducts.indexOf('amasty-catalog-topnav') == -1) {
                $(this.selectors.top_navigation).first().replaceWith(data.navigationTop);
                $(this.selectors.top_navigation).first().trigger('contentUpdated');
            }

            var mainContent  = data.categoryProducts || data.cmsPageData;

            if (mainContent) {
                $productsWrapper.replaceWith(mainContent);
                // we should reinitialize element - because it was replaced and removed
                $productsWrapper = this.getProductBlock();
                try {
                    if (typeof $.fn.applyBindings !== 'undefined') {
                        ko.cleanNode($productsWrapper)
                        $productsWrapper.applyBindings();
                    }
                    $productsWrapper.trigger('contentUpdated');
                } catch (e) {
                    //do nothing. error with third party extension
                }
            }

            $(this.selectors.title_head).closest('.page-title-wrapper').replaceWith(data.h1);
            $(this.selectors.title_head).trigger('contentUpdated');

            this.replaceBlock('.breadcrumbs', 'breadcrumbs', data);
            this.replaceBlock('.switcher-currency', 'currency', data);
            this.replaceBlock('.switcher-language', 'store', data);
            this.replaceBlock('.switcher-store', 'store_switcher', data);
            this.replaceCategoryView(data);

            if (data.behaviour) {
                this.updateMultipleWishlist(data.behaviour);
            }

            $(window).trigger('google-tag');

            mediaCheck({
                media: '(max-width: 768px)',
                entry: function () {
                    amShopbyTopFilters.moveTopFiltersToSidebar();
                    if (selectMainNavigation == selectTopNavigation
                        && $(selectSidebarNavigation + ':not(.amshopby-all-top-filters-append-left)').length != 0) {
                        $(selectSidebarNavigation).first().remove();
                    }
                },
                exit: function () {
                    amShopbyTopFilters.removeTopFiltersFromSidebar();
                }
            });
            var swatchesTooltip = $('.swatch-option-tooltip');
            if (swatchesTooltip.length) {
                swatchesTooltip.hide();
            }

            if (this.$shopbyOverlay) {
                this.$shopbyOverlay.hide();
            }

            var productList = $(this.selectors.products_wrapper).last();

            this.scrollUp();

            $('.amshopby-filters-bottom-cms').remove();
            productList.append(data.bottomCmsBlock);

            $(this.selectors.js_init).first().replaceWith(data.js_init);
            $(this.selectors.js_init).first().trigger('contentUpdated');

            this.afterChangeContentExternal(productList);
            this.initAjax();
        },

        /**
         * @public
         * @return {Object}
         */
        getProductBlock: function () {
            var $productsWrapper = $(this.selectors.products_wrapper).last();

            if ($productsWrapper.parent('.search.results').length) {
                $productsWrapper = $productsWrapper.parent('.search.results');
            }

            return $productsWrapper;
        },

        scrollUp: function () {
            var productList = $(this.selectors.products_wrapper).last(),
                topNavBlock = $(this.selectors.top_nav);

            if (this.options.scrollUp && productList.length) {
                $(document).scrollTop(this.options.scrollUp === 1
                    ? (topNavBlock.length ? topNavBlock.offset().top : productList.offset().top)
                    : 0);
            }
        },

        afterChangeContentExternal: function (productList) {
            //compatibility with Amasty Scroll extension
            $(document).trigger('amscroll_refresh');

            //fix issue with wrong form key
            productList.formKey();

            //porto theme compatibility
            var lazyImg = $("img.porto-lazyload:not(.porto-lazyload-loaded)");
            if (lazyImg.length && typeof $.fn.lazyload == 'function') {
                lazyImg.lazyload({effect:"fadeIn"});
            }

            if ($('head').html().indexOf('Infortis') > -1) {
                $(document).trigger('last-swatch-found');
            }
        },

        updateMultipleWishlist: function (data) {
            $('#popup-tmpl').remove();
            $('#split-btn-tmpl').remove();
            $('#form-tmpl-multiple').replaceWith(data);
            $('body').off('click', '[data-post-new-wishlist]');
            require('uiRegistry').remove('multipleWishlist');
            $('.page-wrapper').trigger('contentUpdated');
        },

        replaceBlock: function (blockClass, dataName, data) {
            $(blockClass).replaceWith(data[dataName]);
            $(blockClass).trigger('contentUpdated');
        },

        replaceCategoryView: function (data) {
            var imageElement = $(".category-image"),
                descrElement = $(".category-description");
            if (data.image) {
                 if (imageElement.length !== 0) {
                     imageElement.replaceWith(data.image);
                 } else {
                     imageElement.prependTo("column.main");
                 }
            } else {
                imageElement.remove();
            }


            if (data.description) {
                if (descrElement.length !== 0) {
                    descrElement.replaceWith(data.description);
                } else {
                    if (imageElement.length !== 0) {
                        $(data.description).insertAfter(imageElement.selector);
                    } else {
                        descrElement.prependTo("column.main");
                    }
                }
            } else {
                descrElement.remove();
            }

            $('title').html(data.title);
            if (data.categoryData) {
                var categoryViewSelector = ".category-view";
                if ($(categoryViewSelector).length === 0) {
                    $('<div class="category-view"></div>').insertAfter('.page.messages');
                }
                $(categoryViewSelector).replaceWith(data.categoryData);
            }
        },

        generateOverlayElement: function () {
            var selectors = this.selectors,
                productListContainer = $(selectors.products_wrapper
                    .replace(',', ' ' + selectors.products + ',') + ' ' + selectors.products);

            if (!$(this.selectors.overlay).length) {
                productListContainer.append(this.nodes.loader);
            }

            this.$shopbyOverlay = $(this.selectors.overlay);
        },

        initAjax: function () {
            var self = this;
            this.generateOverlayElement();

            if ($.mage.productListToolbarForm) {
                //change page limit
                $.mage.productListToolbarForm.prototype.changeUrl = function (paramName, paramValue, defaultValue) {
                    // Workaround to prevent double method call
                    if (self.blockToolbarProcessing) {
                        return;
                    }
                    self.blockToolbarProcessing = true;
                    setTimeout(function () {
                        self.blockToolbarProcessing = false;
                    }, 300);

                    var decode = window.decodeURIComponent,
                        urlPaths = this.options.url.split('?'),
                        urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                        paramData = {},
                        currentPage = this.getCurrentPage ? this.getCurrentPage() : '1',
                        newPage;

                    for (var i = 0; i < urlParams.length; i++) {
                        var parameters = urlParams[i].split('=');
                        paramData[decode(parameters[0])] = parameters[1] !== undefined
                            ? decode(parameters[1].replace(/\+/g, '%20'))
                            : '';
                    }

                    if (currentPage > 1 && paramName === this.options.limit) {
                        newPage = Math.floor(this.getCurrentLimit() * (currentPage - 1) / paramValue) + 1;

                        if (newPage > 1) {
                            paramData[this.options.page] = newPage;
                        } else {
                            delete paramData[this.options.page];
                        }
                    }

                    paramData[paramName] = paramValue;

                    if (paramValue == defaultValue) {
                        delete paramData[paramName];
                    }

                    if (self.options.isMemorizerAllowed) {
                        self.memorizeData.push({name: paramName, value: paramValue});
                    }

                    self.options.clearUrl = self.getNewClearUrl(
                        paramName,
                        paramData[paramName] ? paramData[paramName] : '',
                        paramData[this.options.page]
                    );

                    //add ajax call
                    $.mage.amShopbyFilterAbstract.prototype.prepareTriggerAjax(null, null, null, true);
                };
            }

            //change page number
            $(".toolbar .pages a").unbind('click').bind('click', function (e) {
                var newUrl = $(this).prop('href'),
                    updatedUrl = null,
                    urlPaths = newUrl.split('?'),
                    urlParams = urlPaths[1] ? urlPaths[1].split('&') : [];

                for (var i = 0; i < urlParams.length; i++) {
                    if (urlParams[i].indexOf("p=") === 0) {
                        var pageParam = urlParams[i].split('=');
                        updatedUrl = self.getNewClearUrl(pageParam[0], pageParam[1] > 1 ? pageParam[1] : '');
                        break;
                    }
                }

                if (!updatedUrl) {
                    updatedUrl = this.href;
                }
                updatedUrl = updatedUrl.replace('amp;', '');
                $.mage.amShopbyFilterAbstract.prototype.prepareTriggerAjax(document, updatedUrl, false, true);
                $(document).scrollTop($(self.selectors.products_wrapper).offset().top);

                e.stopPropagation();
                e.preventDefault();
            });
        },

        //Update url after change page size or current page.
        getNewClearUrl: function (key, value, page) {
            var url = new URL(window.location.href),
                params = new window.URLSearchParams(url.search);

            if (value !== '') {
                params.set(key, value);
            } else {
                params.delete(key);
            }

            if (page) {
                params.set('p', page);
            }

            url.search = params;

            return window.decodeURIComponent(url.toString());
        }
    });

    return $.mage.amShopbyAjax;
});
