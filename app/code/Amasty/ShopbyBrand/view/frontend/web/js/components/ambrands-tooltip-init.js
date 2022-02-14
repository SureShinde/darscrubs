/**
 * @return widget
 */

define([
    'jquery',
    'mage/tooltip'
], function ($) {
    'use strict';

    $.widget('am.brandsTooltipInit', {
        classes: {
            tooltip: 'amshopby-brand-tooltip',
            arrow: 'arrow'
        },

        /**
         * @private
         */
        _create: function () {
            var self = this,
                current;

            $(this.element).tooltip({
                position: {
                    my: 'left-20 bottom',
                    at: 'right top',
                    collision: 'flip flip',
                    using: function (position, feedback) {
                        $(this).css(position);

                        $('<div>')
                            .addClass(self.classes.arrow)
                            .addClass(feedback.vertical)
                            .addClass(feedback.horizontal)
                            .appendTo(this);
                    },
                },
                tooltipClass: self.classes.tooltip,
                content: function () {
                    current = $(this).is('li') ? $(this) : $(this).parent();

                    return current.data('tooltip-content');
                }
            });
        }
    });

    return $.am.brandsTooltipInit;
});
