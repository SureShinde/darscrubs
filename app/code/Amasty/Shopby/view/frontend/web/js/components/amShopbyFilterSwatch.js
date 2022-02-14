/**
 *  Amasty Swatch Component
 */

define([
    'jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterSwatch', $.mage.amShopbyFilterAbstract, {
        options: {},
        selectors: {
            swatchFilterName: '[name="amshopby[{value}][]"]',
            swatchOption: '.swatch-option'
        },
        clickEvent: 'click.amShopbySwatch',
        classes: {
            swatchSelected: 'selected'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                inputSelector = this.getInputSelector(),
                input;

            self.element.find('a').off(self.clickEvent).on(self.clickEvent, function (event) {
                event.stopPropagation();
                event.preventDefault();

                input = $(this).siblings(inputSelector);

                input.prop('checked', self.isChecked($(this)) ? 0 : 1);
                input.trigger('change');
                input.trigger('sync', [!input.prop('checked')]);
                self.renderShowButton(event, this);
                self.apply($(this).attr('href'));
                self.markSelected.bind(self);
            });

            self.element.find(inputSelector).on('amshopby:sync_change', self.markSelected.bind(self));
            self.markSelected.bind(self);
        },

        /**
         * @public
         * @return {void}
         */
        markSelected: function () {
            var self = this;

            self.element.find('a').each(function () {
                $(this)
                    .find(self.selectors.swatchOption)
                    .toggleClass(self.classes.swatchSelected, self.isChecked($(this)));
            });
        },

        /**
         * @public
         * @return {String}
         */
        getInputSelector: function () {
            var value = this.element.closest(this.selectors.filterForm).attr('data-amshopby-filter');

            return this.selectors.swatchFilterName.replace('{value}', value);
        },

        /**
         * @public
         * @param {Object} element - jQuery
         * @return {Boolean}
         */
        isChecked: function (element) {
            return element.siblings(this.getInputSelector()).prop('checked');
        }
    });

    return $.mage.amShopbyFilterSwatch;
});
