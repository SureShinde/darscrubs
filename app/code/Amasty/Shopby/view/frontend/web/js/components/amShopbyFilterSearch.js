/**
 *  Amasty Search Component
 */

define([
    'jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterSearch', {
        options: {
            highlightTemplate: '',
            itemsSelector: ''
        },
        selectors: {
            item: '.item',
            swatchLink: '.am-swatch-link',
            filterOptionsContent: '.filter-options-content',
            filterContainer: '[data-am-js="shopby-container"]',
            label: '.label'
        },
        classes: {
            hidden: '-amshopby-hidden'
        },
        previousSearch: '',

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                items = $(this.options.itemsSelector
                    + ' ' + this.selectors.item +  ', ' + this.options.itemsSelector + ' ' + this.selectors.swatchLink);

            $(self.element).keyup(function () {
                self.search(this.value, items);
            });
        },

        /**
         * @public
         * @param {String} searchText
         * @param {Object} items
         * @return {void}
         */
        search: function (searchText, items) {
            var self = this,
                value,
                parentSelector = !self.options.isState
                    ? self.selectors.filterOptionsContent : self.selectors.filterContainer;

            searchText = searchText.toLowerCase();

            if (searchText === self.previousSearch) {
                return;
            }

            self.previousSearch = searchText;

            if (searchText !== '') {
                $(self.element).trigger('search_active');
            }

            items.each(function (key, item) {
                if (item.hasAttribute('data-label')) {
                    value = item.getAttribute('data-label').toLowerCase();

                    if (!value || value.indexOf(searchText) > -1) {
                        if (searchText !== '' && value.indexOf(searchText) > -1) {
                            self.highlight(item, searchText);
                        } else {
                            self.unHighlight(item);
                        }

                        $(item).parent().removeClass(self.classes.hidden);
                        $(item).show();
                        $(item).parentsUntil(parentSelector).show();
                    } else {
                        self.unHighlight(item);
                        $(item).parent().addClass(self.classes.hidden);
                        $(item).hide();
                    }
                }
            });

            if (searchText === '') {
                $(self.element).trigger('search_inactive');
            }
        },

        /**
         * @public
         * @param {Object} element
         * @param {String} searchText
         * @return {void}
         */
        highlight: function (element, searchText) {
            var target = $(element).find('a').length !== 0 ? $(element).find('a') : $(element),
                label = $(element).attr('data-label'),
                newLabel = label.replace(new RegExp(searchText, 'gi'), this.options.highlightTemplate);

            this.unHighlight(element);
            target.find(this.selectors.label).html(newLabel);
        },

        /**
         * @public
         * @param {Object} element
         * @return {void}
         */
        unHighlight: function (element) {
            var target = $(element).find('a').length !== 0 ? $(element).find('a') : $(element),
                label = $(element).attr('data-label');

            target.find(this.selectors.label).html(label);
        }
    });

    return $.mage.amShopbyFilterSearch;
});
