/**
 *  Amasty Filter Abstract
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/slider',
    'mage/tooltip',
    'mage/validation',
    'mage/translate',
    'Amasty_Shopby/js/jquery.ui.touch-punch.min',
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterAbstract', {
        filters: {},
        options: {
            isAjax: 0,
            collectFilters: 0,
            activeClass: '-active',
            clearUrl: null,
            delta: [],
            deltaFrom: 0,
            deltaTo: 0,
            curRate: '1'
        },
        selectors: {
            fromToWidget: '[data-am-js="fromto-widget"]',
            filterForm: 'form[data-amshopby-filter]',
            filterFormAttr: 'form[data-amshopby-filter={attr}]',
            filterItems: '[class*="am-filter-items"]',
            sidebar: '.sidebar',
            topNav: '.catalog-topnav',
            filterOptionsContent: '.filter-options-content',
            filterOptionsItem: '.filter-options-item',
            ajaxOptionsSelectors: 'body.page-with-filter, body.catalogsearch-result-index, body.cms-index-index',
            filterRequestVar: '[data-amshopby-filter-request-var="cat"]',
            dFrom: 'df=',
            dTo: 'dt='
        },
        classes: {
            active: '-active',
            disabled: '-disabled',
            savedFilterValues: 'amshopby-saved-values',
            selected: 'amshopby-link-selected'
        },

        /**
         * @public
         * @return {Object}
         */
        getFilter: function () {
            return {
                'code': this.element.attr('amshopby-filter-code'),
                'value': this.element.attr('amshopby-filter-value')
            };
        },

        /**
         * @public
         * @param link
         * @param clearFilter
         * @return {void}
         */
        apply: function (link, clearFilter) {
            var linkParam;

            try {
                if ($.mage.amShopbyAjax) {
                    $.mage.amShopbyAjax.prototype.response = null;
                }

                this.options.isAjax = $.mage.amShopbyAjax !== 'undefined';

                linkParam = clearFilter ? link : null;

                if (!this.options.collectFilters && this.options.isAjax === true) {
                    this.prepareTriggerAjax(this.element, linkParam, clearFilter);
                } else {
                    if (this.options.collectFilters === 1) {
                        this.prepareTriggerAjax(this.element, linkParam);
                    } else {
                        window.location = link;
                    }
                }
            } catch (e) {
                window.location = link;
            }
        },

        /**
         * @public
         * @return {Array}
         */
        prepareTriggerAjax: function (element, clearUrl, clearFilter, isSorting) {
            var self = this,
                widgetInstance = $.mage.amShopbyFilterAbstract.prototype,
                selectors = this.selectors,
                forms = $(selectors.filterForm),
                attributeName,
                excludedFormSelector,
                existFields = [],
                savedFilters = [],
                $item,
                className,
                startPos,
                endPos,
                filterClass,
                isPriceType,
                serializeForms,
                isPriceExist,
                data;

            if (typeof this.element !== 'undefined' && clearFilter) {
                attributeName = this.selectors.filterFormAttr
                    .replace('{attr}', this.element
                        .closest(selectors.filterOptionsContent)
                        .find('form')
                        .data('amshopby-filter'));
                excludedFormSelector = ((this.element.closest(selectors.sidebar).length === 0)
                    ? selectors.topNav : selectors.sidebar) + ' ' + attributeName;

                forms = forms.not(excludedFormSelector);
            }

            forms.each(function (index, item) {
                $item = $(item);
                className = '';

                if ($item.closest(selectors.filterItems).length) {
                    className = $item.closest(selectors.filterItems)[0].className;
                } else if ($item.find(selectors.filterItems).length) {
                    className = $item.find(selectors.filterItems)[0].className;
                }

                startPos = className.indexOf('am-filter-items');
                endPos = className.indexOf(' ', startPos + 1) === -1 ? 100 : className.indexOf(' ', startPos + 1);
                filterClass = className.substring(startPos, endPos);
                isPriceType = $($item.closest(selectors.filterOptionsItem))
                    .find(selectors.fromToWidget).length;

                if (filterClass && existFields[filterClass] && !isPriceType) {
                    forms[index] = '';
                } else {
                    existFields[filterClass] = true;
                }

                if ($item.hasClass(self.classes.savedFilterValues)) {
                    savedFilters.push(forms[index]);
                    forms[index] = '';
                }
            });

            serializeForms = forms.serializeArray();
            isPriceExist = false;

            _.each(serializeForms, function (index, item) {
                if (item['name'] === 'amshopby[price][]') {
                    isPriceExist = true;
                    return false;
                }
            });

            if (!isPriceExist && savedFilters) {
                savedFilters.forEach(function (element) {
                    serializeForms.push($(element).serializeArray()[0]);
                });
            }

            data = this.normalizeData(serializeForms, isSorting, clearFilter);
            data.clearUrl = data.clearUrl ? data.clearUrl : clearUrl;
            element = element ? element : document;

            if (widgetInstance.options.delta.length) {
                data = data.concat(widgetInstance.options.delta);
            }

            $(element).trigger('amshopby:submit_filters', {
                data: data,
                clearFilter: clearFilter,
                isSorting: isSorting
            });

            return data;
        },

        /**
         * @public
         * @return {Array}
         */
        normalizeData: function (data, isSorting, clearFilter) {
            var self = this,
                normalizedData = [],
                ajaxOptions = $(this.selectors.ajaxOptionsSelectors).amShopbyAjax('option'),
                clearUrl;

            _.each(data, function (item) {
                if (item && item.value.trim() !== '' && item.value !== '-1') {
                    var isNormalizeItem = _.find(normalizedData, function (normalizeItem) {
                        return normalizeItem.name === item.name && normalizeItem.value === item.value
                            || item.name === 'amshopby[price][]' && normalizeItem.name === item.name;
                    });

                    if (!isNormalizeItem) {
                        if (item.name === 'amshopby[price][]') {
                            item.value = self.normalizePrice(item.value);
                        }

                        normalizedData.push(item);

                        if (ajaxOptions.isCategorySingleSelect === '1'
                            && item.name === 'amshopby[cat][]'
                            && item.value !== ajaxOptions.currentCategoryId
                            && !clearFilter
                            && !isSorting
                        ) {
                            clearUrl = $('*' + self.selectors.filterRequestVar + ' *[value="' + item.value + '"]')
                                .parent().find('a').attr('href');
                        }
                    }
                }
            });

            normalizedData = this.groupDataByName(normalizedData);

            if (clearUrl) {
                normalizedData.clearUrl = clearUrl;
            }

            return normalizedData;
        },

        /**
         * @public
         * @return {Array}
         */
        groupDataByName: function (formData, fn) {
            var hash = Object.create(null);

            return formData.reduce(function (result, currentValue) {
                if (!hash[currentValue['name']]) {
                    hash[currentValue['name']] = {};
                    hash[currentValue['name']]['name'] = currentValue['name'];
                    result.push(hash[currentValue['name']]);
                }

                if (hash[currentValue['name']].value) {
                    hash[currentValue['name']].value += ',' + currentValue.value;
                } else {
                    hash[currentValue['name']].value = currentValue.value;
                }

                return result;
            }, []);
        },

        /**
         * @public
         * @return {Number}
         */
        getSignsCount: function (step, isPrice) {
            return 2;
        },

        /**
         * @public
         * @return {Number}
         */
        getFloatNumber: function (size) {
            if (!size) {
                size = 2;
            }

            return 1 / parseInt(this.buildNumber(size));
        },

        /**
         * @public
         * @return {String}
         */
        buildNumber: function (size) {
            var str = '1',
                i;

            for (i = 1; i <= size; i++) {
                str += '0';
            }

            return str;
        },

        /**
         * @public
         * @return {Number}
         */
        getFixed: function (value, isPrice) {
            return value ? this.getSignsCount(this.options.step, isPrice) : 2;
        },

        /**
         * @public
         * @return {Boolean}
         */
        isPrice: function () {
            return (typeof this.options.code != 'undefined' && this.options.code === 'price');
        },

        /**
         * @public
         * @return {void}
         */
        renderShowButton: function (event, element) {
            if ($.mage.amShopbyApplyFilters) {
                $.mage.amShopbyApplyFilters.prototype.renderShowButton(event, element);
            }
        },

        /**
         * @public
         * @return {void}
         */
        addListenerOnCheckbox: function (checkbox, parent) {
            checkbox.bind('click', {}, function (event) {
                event.stopPropagation();
                event.currentTarget.checked = !event.currentTarget.checked;
                parent.trigger('click');
            });
        },

        /**
         * @public
         * @param {Object} element - jQuery
         * @return {Boolean}
         */
        markAsSelected: function (element) {
            var self = this;

            element.closest('form').find('a').each(function () {
                $(this).toggleClass(self.classes.selected, $(this).siblings('input').prop('checked'));
            });
        },

        /**
         * @public
         * @param {string} value
         * @return {string}
         */
        normalizePrice: function (value) {
            var result = value.split('-'),
                price,
                i;

            for (i = 0; i < result.length; i++) {
                if (typeof result[i] == 'undefined') {
                    result[i] = 0;
                }

                result[i] = this.processPrice(true, result[i]).toFixed(2);
            }

            return result.join('-').replace(/[ \r\n]/g, '');
        },

        /**
         * @public
         * @returns {boolean}
         */
        isBaseCurrency: function () {
            return Number(this.options.curRate) === 1;
        },

        /**
         * @public
         * @param {boolean} toBasePrice
         * @param {string | number} input
         * @param {string | number} delta
         * @returns {number}
         */
        processPrice: function (toBasePrice, input, delta) {
            var rate = Number(this.options.curRate),
                inputPrice = Number(input),
                result;

            delta = typeof delta !== 'undefined' ? Number(delta) : 0;

            return this.isBaseCurrency() ? inputPrice : (toBasePrice ? (inputPrice / rate) : ((inputPrice * rate) + delta));
        },

        /**
         * @public
         * @param {string | number} calculatedCurrency
         * @param {string | number} baseCurrency
         * @returns {string}
         */
        calculateDelta: function (calculatedCurrency, baseCurrency) {
            return (Number(calculatedCurrency) - (Number(baseCurrency).toFixed(2) * Number(this.options.curRate))).toFixed(3);
        },

        /**
         * @public
         * @param {string} link
         * @param {string | number} valueFrom - base currency price
         * @param {string | number} from - processed price
         * @param {string | number} valueTo - base currency price
         * @param {string | number} to - processed price
         * @param {string} deltaFrom
         * @param {string} deltaTo
         * @return {string}
         */
        getUrlWithDelta: function (link, valueFrom, from, valueTo, to, deltaFrom, deltaTo) {
            var dFrom = this.selectors.dFrom,
                dTo = this.selectors.dTo;

            if (this.isBaseCurrency()) {
                return link;
            }

            if (link.indexOf(dFrom) !== -1 || link.indexOf(dTo) !== -1) {
                link = link.replace(dFrom + parseFloat(deltaFrom).toFixed(2),
                    dFrom + this.calculateDelta(from, valueFrom))
                    .replace(dTo + parseFloat(deltaTo).toFixed(2),
                        dTo + this.calculateDelta(to, valueTo));
            } else {
                link += this.getDeltaParams(from, valueFrom, to, valueTo, true);
            }

            return link;
        },

        /**
         * @public
         * @param {string | number} from
         * @param {string | number} valueFrom
         * @param {string | number} to
         * @param {string | number} valueTo
         * @param {boolean} isUrl
         * @returns {string | array}
         */
        getDeltaParams: function (from, valueFrom, to, valueTo, isUrl) {
            var deltaFrom = this.calculateDelta(from, valueFrom),
                deltaTo = this.calculateDelta(to, valueTo),
                params = '';

            $.mage.amShopbyFilterAbstract.prototype.options.deltaFrom = deltaFrom;
            $.mage.amShopbyFilterAbstract.prototype.options.deltaTo = deltaTo;

            if (isUrl) {
                if (deltaFrom % 1 != 0) {
                    params += '&df=' + deltaFrom;
                }
                if (deltaTo % 1 != 0) {
                    params += '&dt=' + deltaTo;
                }
                return params;
            } else {
                return [
                    {name: 'amshopby[df][]', value: deltaFrom},
                    {name: 'amshopby[dt][]', value: deltaTo}
                ]
            }
        },

        /**
         * @public
         * @param {array} delta
         * @returns {void}
         */
        setDeltaParams: function (delta) {
            $.mage.amShopbyFilterAbstract.prototype.options.delta = delta instanceof Array ? delta : [];
        },

        /**
         * @public
         * @param {string | number} curRate
         * @returns {void}
         */
        setCurrency: function (curRate) {
            this.options.curRate = curRate;
        }
    });

    return $.mage.amShopbyFilterAbstract;
});
