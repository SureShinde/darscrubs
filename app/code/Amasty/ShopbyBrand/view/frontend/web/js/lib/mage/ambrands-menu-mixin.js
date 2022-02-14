/**
 * Extend mage menu
 */

define([
    'jquery'
], function ($) {
    'use strict';

    var mixin = {

        /**
         * Prevent error when this.active is null because of click on brands dropdown area
         *
         * @param {jQuery.Event} event
         * @private
         */
        _keydown: function (event) {
            if (!this.active) {
                return;
            }

            this._super(event);
        }
    };

    return function (targetWidget) {
        $.widget('mage.menu', targetWidget.menu, mixin);

        return {
            menu: $.mage.menu,
            navigation: $.mage.navigation
        };
    };
});
