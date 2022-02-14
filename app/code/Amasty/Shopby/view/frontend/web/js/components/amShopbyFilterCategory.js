/**
 *  Amasty Filter Category Component
 */

define([
    'jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterCategory', $.mage.amShopbyFilterAbstract, {
        options: {
            type: null,
            collectFilters: null,
            clearUrl: null
        },
        selectors: {
            items: '.items',
            parent: '.item',
            radioButton: 'input[type=radio]',
            checkboxButton: 'input[type=checkbox]',
            checkbox: 'input[type=checkbox], input[type=radio]'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                parent = $(self.element.parents(self.selectors.parent)[0]),
                checkbox = self.element.siblings(self.selectors.checkbox);

            this.initEventListeners();
            this.addListenerOnCheckbox(checkbox, parent);
            this.markAsSelected(checkbox);
        },

        /**
         * @public
         * @return {void}
         */
        initEventListeners: function () {
            var self = this,
                link = self.element,
                parent = $(link.parents(self.selectors.parent)[0]),
                checkbox = link.siblings(self.selectors.checkbox),
                params = {
                    parent: parent,
                    checkbox: checkbox,
                    link: link
                },
                element,
                href;

            parent.off('click').bind('click', params, function (event) {
                element = event.data.checkbox;
                href = event.data.link.prop('href');

                event.stopPropagation();
                event.preventDefault();

                if ($(this).find(self.selectors.radioButton)[0] && location.href.indexOf('find=') !== -1) {
                    location.href = href;

                    return;
                }

                element.prop('checked', !element.prop('checked'));
                self.triggerSync(element, !element.prop('checked'));
                self.renderShowButton(event, element);
                self.apply(href);
                self.togglingTree(event.data.parent, self.isTypeFolding() ? false : element.prop('checked'));
            });

            checkbox.on('change', function () {
                self.markAsSelected($(this));
            });

            checkbox.on('amshopby:sync_change', function () {
                self.markAsSelected($(this));
            });
        },

        /**
         * @public
         * @param {Object} element - jQuery
         * @param {Boolean} clearFilter
         * @return {void}
         */
        triggerSync: function (element, clearFilter) {
            element.trigger('change');
            element.trigger('sync', [clearFilter]);
        },

        /**
         * @public
         * @param {Object} element - jQuery
         * @param {Boolean} isChecked
         * @return {void}
         */
        togglingTree: function (element, isChecked) {
            if (this.isTypeFolding()) {
                element
                    .find(this.selectors.items + ' ' + this.selectors.checkboxButton)
                    .prop('checked', false);
            }

            element
                .parents(this.selectors.parent)
                .find('> a ' + this.selectors.checkboxButton).prop('checked', isChecked);
        },

        /**
         * @public
         * @return {Boolean}
         */
        isTypeFolding: function () {
            return this.options.type === 'labelsFolding';
        }
    });

    return $.mage.amShopbyFilterCategory;
});
