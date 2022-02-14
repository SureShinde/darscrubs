/**
 *  Amasty Filter Abstract
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterHideMoreOptions', {
        options: {
            numberUnfoldedOptions: 0,
            buttonSelector: '',
            isState: null,
            isHideCurrent: false
        },
        classes: {
            active: '-active',
            disabled: '-disabled'
        },
        selectors: {
            filterOptionsContent: '.filter-options-content',
            filterContainer: '[data-am-js="shopby-container"]',
            item: '.item',
            filterItem: '[data-am-js="shopby-item"]',
            counter: '[data-am-counter="counter"]'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                selectors = this.selectors,
                buttons = $(self.options.buttonSelector);

            self.parentSelector = !self.options.isState ? selectors.filterOptionsContent : selectors.filterContainer;
            self.filterItemSelector = !self.options.isState ? selectors.item : selectors.filterItem;

            buttons.each(function () {
                $(this).addClass(self.isButtonActive($(this)) ? self.classes.active : self.classes.disabled);
            });

            this.initListeners();

            // for hide in first load
            buttons.each(function (index, element) {
                if (!$(element).attr('first_load')) {
                    $(element).attr('first_load', true);
                    $(element).click();
                }
            });
        },

        /**
         * @public
         * @return {void}
         */
        initListeners: function () {
            var self = this,
                buttons = $(self.options.buttonSelector);

            $(this.element)
                .closest(self.selectors.filterOptionsContent)
                .on('search_active', function () {
                    if (self.options.isHideCurrent) {
                        self.toggle(self.options.buttonSelector);
                    }

                    buttons.removeClass(self.classes.active);
                });

            $(this.element)
                .closest(self.selectors.filterOptionsContent)
                .on('search_inactive', function () {
                    if (!buttons.hasClass(self.classes.disabled)) {
                        if (!self.options.isHideCurrent) {
                            self.toggle(self.options.buttonSelector);
                        }

                        buttons.addClass(self.classes.active);
                    }
                });

            buttons.unbind('click').click(function () {
                self.toggle(this);
            });
        },

        /**
         * @public
         * @param {Object} element - jQuery
         * @return {Boolean}
         */
        isButtonActive: function (element) {
            return element
                .closest(this.parentSelector)
                .find(this.filterItemSelector).length > this.options.numberUnfoldedOptions;
        },

        /**
         * @public
         * @param {Object} buttons - jQuery
         * @return {void}
         */
        toggle: function (buttons) {
            var elements = $(buttons),
                count;

            if (elements[0].isHideCurrent) {
                this.showAll(elements);
                elements.html(elements.attr('data-text-less'));
                elements.attr({
                    'data-is-hide': 'false',
                    'title': elements.text()
                });

                elements[0].isHideCurrent = false;
            } else {
                count = this.hideAll(elements);

                elements.html(elements.attr('data-text-more'));
                elements.find(this.selectors.counter).html(count);
                elements.attr({
                    'data-is-hide': 'true',
                    'title': elements.text()
                });

                elements[0].isHideCurrent = true;
            }
        },

        /**
         * @public
         * @param {Object} buttons - jQuery
         * @return {Number}
         */
        hideAll: function (buttons) {
            var self = this,
                count = 0,
                hideCount = 0;

            buttons.closest(self.parentSelector).find(self.filterItemSelector).each(function () {
                if (++count > self.options.numberUnfoldedOptions) {
                    hideCount++;

                    $(this).hide();
                }
            });

            return hideCount;
        },

        /**
         * @public
         * @param {Object} buttons - jQuery
         * @return {void}
         */
        showAll: function (buttons) {
            buttons.closest(this.parentSelector).find(this.filterItemSelector).show();
        }
    });

    return $.mage.amShopbyFilterHideMoreOptions;
});
