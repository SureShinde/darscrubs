define([
    'jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterItemDefault', $.mage.amShopbyFilterAbstract, {
        options: {},
        selectors: {
            parent: '.item',
            checkbox: 'input[type=checkbox], input[type=radio]',
            nameCategory: '[name="amshopby[cat][]"]'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                link = self.element,
                parent = link.closest(self.selectors.parent),
                checkbox = link.siblings(self.selectors.checkbox),
                params = {
                    parent: parent,
                    checkbox: checkbox,
                    link: link
                };

            if (link.find(self.selectors.nameCategory).length && parent) {
                parent = $(null); // get only current category item
            }

            checkbox.bind('click', params, function (event) {
                var checkbox = $(this),
                    link = event.data.link,
                    href = link.prop('href');

                event.stopPropagation();

                setTimeout(function () {
                    self.triggerSync(checkbox, !checkbox.prop('checked'));

                    if (self.isFinderAndCategory(checkbox[0])) {
                        location.href = href;

                        return;
                    }

                    self.renderShowButton(event, link);

                    self.apply(href);
                }, 10);
            });

            link.bind('click', params, function (e) {
                var element = e.data.checkbox,
                    href = e.data.link.prop('href');

                e.stopPropagation();
                e.preventDefault();

                element.prop('checked', !element.prop('checked'));
                self.triggerSync(element, !element.prop('checked'));

                if (self.isFinderAndCategory(element[0])) {
                    location.href = href;

                    return;
                }

                self.renderShowButton(e, element);
                self.apply(href);
            });

            parent.off('click').bind('click', params, function (e) {
                var element = e.data.checkbox,
                    link = e.data.link;

                e.stopPropagation();
                e.preventDefault();

                element.prop('checked', !element.prop('checked'));
                self.triggerSync(element, !element.prop('checked'));
                self.renderShowButton(e, element);
                self.apply(link.prop('href'));

                return false;
            });

            checkbox.on('change', function (e) {
                self.markAsSelected($(this));
            });

            checkbox.on('amshopby:sync_change', function (e) {
                self.markAsSelected($(this));
            });

            self.markAsSelected(checkbox);
        },

        /**
         * @public
         * @param {Object} element
         * @param {Boolean} clearFilter
         * @return {void}
         */
        triggerSync: function (element, clearFilter) {
            element.trigger('change');
            element.trigger('sync', [clearFilter]);
        },

        /**
         * @public
         * @param {Object} element
         * @return {Boolean}
         */
        isFinderAndCategory: function (element) {
            return location.href.indexOf('find=') !== -1
                && element.type === 'radio'
                && element.name === 'amshopby[cat][]';
        },
    });

    return $.mage.amShopbyFilterItemDefault;
});
