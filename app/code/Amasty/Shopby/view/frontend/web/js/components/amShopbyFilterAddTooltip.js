/**
 *  Amasty Add Tooltip Component
 */

define([
    'jquery',
    'mage/tooltip'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterAddTooltip', {
        options: {
            content: '',
            tooltipTemplate: ''
        },
        selectors: {
            optionsTitle: '.filter-options-title',
            optionsItem: '.filter-options-item',
            tooltip: '.tooltip'
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var template = $(this.options.tooltipTemplate.replace('{content}', this.options.content)),
                place = $(this.element).parents(this.selectors.optionsItem).find(this.selectors.optionsTitle);

            if (place.length === 0) {
                place = $(this.element).parents('dd').prev('dt');
            }

            if (place.length > 0 && !place.find(this.selectors.tooltip).length) {
                place.append(template);

                template.tooltip({
                    position: {
                        my: 'left bottom-10',
                        at: 'left top',
                        collision: 'flipfit flip',
                        using: function (position, feedback) {
                            $(this).css(position);
                            $('<div>')
                                .addClass('arrow')
                                .addClass(feedback.vertical)
                                .addClass(feedback.horizontal)
                                .appendTo(this);
                        }
                    },
                    content: function () {
                        return $(this).prop('title');
                    }
                });
            }
        }
    });

    return $.mage.amShopbyFilterAddTooltip;
});
