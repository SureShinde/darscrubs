/**
 * @return widget
 */

define([
    'jquery',
    'amBrandsFilter',
    'domReady!'
], function ($) {
    'use strict';

    $.widget('am.brandsFilterInit', {
        options: {
            element: null,
            target: null
        },

        /**
         * @private
         */
        _create: function () {
            var self = this;

            $(this.options.element).on('click', function(e) {
                e.preventDefault();

                $(this).applyBrandFilter(self.options.target);
            });
        }
    });

    return $.am.brandsFilterInit;
});
