/**
 *  Amasty Filter Container Component
 */

define([
    'jquery',
    'jquery-ui-modules/slider',
    'Amasty_Shopby/js/jquery.ui.touch-punch.min',
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterContainer', {
        options: {
            collectFilters: 0
        },
        classes: {
            itemRemoved: 'amshopby-item-removed'
        },
        selectors: {
            filterItem: '[data-am-js="shopby-item"]',
            filterAttrName: '[data-amshopby-filter="attr_{name}"]',
            filterRemoveButton: '[data-container="{attr}"][data-value="{value}"] .amshopby-remove',
            filterContainer: '[data-am-js="shopby-container"]',
            filterActions: '.filter-actions',
            filterName: '[name="amshopby[{name}][]"]',
            swatchOptionSelected: '.swatch-option.selected',
            filterSliderId: '[data-amshopby-slider-id="slider"]',
            filterSliderDisplay: '[data-am-js="slider-display"]',
            filterFromTo: '[data-amshopby-fromto="{type}"]',
            range: '.range',
            filterLayeredBock: '#layered-filter-block',
            blockFilter: '.block.filter'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                links = $(self.element[0]).find(self.selectors.filterItem),
                filters = [];

            if (!links.length) {
                return;
            }

            $(links).each(function (index, value) {
                var filter = {
                    attribute: $(value).attr('data-container'),
                    value: self.escapeHtml($(value).attr('data-value'))
                };

                filters.push(filter);

                $(value).find('a').on('click', function (event) {
                    event.stopPropagation();
                    event.preventDefault();

                    $(this).parent().addClass(self.classes.itemRemoved);
                    $.mage.amShopbyFilterAbstract.prototype.renderShowButton(event, this);
                    self.apply(filter);
                });

                if (filters.length) {
                    $.each(filters, function (index, filter) {
                        self.checkInForm(filter);
                    });
                }
            });
        },

        /**
         * @public
         * @param {String} text
         * @return {String}
         */
        escapeHtml: function (text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return text.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        },

        /**
         * @public
         * @param {Object} filter
         * @return {void}
         */
        apply: function (filter) {
            var self = this,
                attrSelector,
                link = $(self.selectors.filterItem + self.selectors.filterRemoveButton
                    .replace('{attr}', filter.attribute)
                    .replace('{value}', filter.value))
                    .attr('href'),
                value = filter.value;

            try {
                if (filter.attribute === 'price') {
                    attrSelector = self.selectors.filterAttrName.replace('{name}', filter.attribute);
                }

                self.setDefault(filter.attribute, value);

                $(attrSelector).trigger('change');
                $(attrSelector).trigger('sync', [true]);

                if ($.mage.amShopbyAjax !== 'undefined') {
                    $.mage.amShopbyFilterAbstract.prototype.prepareTriggerAjax(null, null, true);
                } else if (this.options.collectFilters !== 1) {
                    window.location = link;
                }
            } catch (e) {
                window.location = link;
            }
        },

        /**
         * @public
         * @return {void}
         */
        clearBlock: function () {
            if (!$(this.selectors.filterContainer).find('li').length) {
                $(this.selectors.filterContainer).remove();
                $(this.selectors.filterActions).remove();
            }
        },

        /**
         * @public
         * @param {String} name
         * @param {String} value
         * @return {void}
         */
        setDefault: function (name, value) {
            var self = this,
                valueSelector = self.selectors.filterName.replace('{name}', name),
                type,
                selected;

            $(valueSelector).each(function (index, filter) {
                type = $(filter).prop('tagName');

                switch (type) {
                    case 'SELECT':
                        if (name === 'price') {
                            $(filter).find('option').each(function (index, element) {
                                if (self.toValidView(element.value.split('-')) === this) {
                                    element.selected = false;
                                }
                            }.bind(value));
                        }

                        $(filter).find('[value="' + value + '"]').removeAttr('selected', 'selected');

                        break;
                    case 'INPUT':
                        selected = '';

                        if ($(filter).attr('type') !== 'text' && $(filter).attr('type') !== 'hidden') {
                            selected = $(valueSelector + '[value="' + value + '"]');

                            selected.removeAttr('checked');
                            selected.siblings(self.selectors.swatchOptionSelected).removeClass('selected');
                        } else if (($(filter).attr('type') === 'hidden'
                            && self.isEquals(name, filter.value, value))
                            || name === 'price'
                        ) {
                            filter.value = '';
                        }

                        break;
                }
            });
        },

        /**
         * @public
         * @param {String} name
         * @param {String} filterValue
         * @param {String} value
         * @return {Boolean}
         */
        isEquals: function (name, filterValue, value) {
            var values = value.split('-'),
                filterValues = filterValue.split('-');

            if (values.length > 1) {
                filterValue = this.toValidView(filterValues);
                value = this.toValidView(values);
            }

            return filterValue === value;
        },

        /**
         * @public
         * @param {Array} values
         * @return {String}
         */
        toValidView: function (values) {
            values[0] = values[0] ? parseFloat(values[0]).toFixed() : values[0];
            values[1] = values[1] ? parseFloat(values[1]).toFixed() : values[1];

            return values[0] + '-' + values[1];
        },

        /**
         * @public
         * @param {Object} filter
         * @return {void}
         */
        checkInForm: function (filter) {
            var name = filter.attribute,
                value = filter.value,
                block;

            if (this.checkIfValueNotExist(name, value)) {
                block = $(this.selectors.filterLayeredBock);

                if (!block.length) {
                    block = $(this.selectors.blockFilter);
                }

                block.append('<form class="amshopby-saved-values" data-amshopby-filter="attr_'
                    + name + '"><input value="' + value + '" type="hidden" name="amshopby[' + name + '][]"></form>');
            }
        },

        /**
         * @public
         * @param {String} name
         * @param {String} value
         * @return {Boolean}
         */
        checkIfValueNotExist: function (name, value) {
            var fromToValueElement = this.selectors.filterFromTo.replace('{type}', 'value'),
                filterItem = $(this.selectors.filterName.replace('{name}', name)),
                notExistValue = true,
                multiSelectOptions,
                splitValue,
                splitCurrentValue;

            filterItem.each(function (index, item) {
                if (item.multiple && $(item).hasClass('am-select')) {
                    multiSelectOptions = [];

                    $(item.selectedOptions).each(function (index, option) {
                        multiSelectOptions.push(option.value);
                    });

                    if (multiSelectOptions.length && multiSelectOptions.includes(value)) {
                        notExistValue = false;
                    }
                } else if (!item.value) {
                    notExistValue = false;
                } else if ($(item).is(fromToValueElement)) {
                    splitValue = value.split('-');
                    splitCurrentValue = item.value.split('-');

                    if (splitValue[0] && splitValue[1] && splitCurrentValue[0] && splitCurrentValue[1]
                        && Number(splitValue[0]) === Number(splitCurrentValue[0])
                        && Number(splitValue[1]) === Number(splitCurrentValue[1])
                    ) {
                        notExistValue = false;
                    }
                } else if (item.value === value) {
                    notExistValue = false;
                }
            });

            return notExistValue;
        }
    });

    return $.mage.amShopbyFilterContainer;
});
