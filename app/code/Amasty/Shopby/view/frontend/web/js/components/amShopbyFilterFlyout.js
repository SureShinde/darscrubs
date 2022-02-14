/**
 * Filter fly-out mode
 */

define([
    'jquery',
    'matchMedia',
    'mage/menu'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterFlyout', {
        options: {
            currentCategoryId: null
        },
        mediaBreakpoint: 'all and (min-width: 768px)',
        triggerType: 'baseCategory',
        classes: {
            jet: 'amasty-jet-theme'
        },
        positions: {
            'desktop': {
                my: 'left top',
                at: 'right top-13',
                collision: 'flipfit'
            },
            'mobile': {
                my: 'center top',
                at: 'center bottom'
            },
            'jet': {
                at: 'right+5 top-6'
            }
        },

        /**
         * inheritDoc
         *
         * @private
         */
        _create: function () {
            this._initNodes();
            this._setPositionForJetTheme();
            this._initEvents();
        },

        /**
         * @private
         */
        _initNodes: function () {
            this.document = $(document);
            this.body = $('body');
        },

        /**
         * @private
         */
        _initEvents: function () {
            var self = this;

            self.document.trigger(self.triggerType, self.options.currentCategoryId);
            self._addFlyOut();

            self.document.ajaxComplete(function () {
                self._addFlyOut();
                self.document.trigger(self.triggerType, self.options.currentCategoryId);
            });
        },

        /**
         * @private
         */
        _addFlyOut: function () {
            this.element.menu({ position: this.positions[this._checkScreenSize() ? 'desktop' : 'mobile'] });
        },

        /**
         * @private
         * @returns {Boolean}
         */
        _checkScreenSize: function () {
            return matchMedia(this.mediaBreakpoint).matches;
        },

        /**
         * @private
         */
        _setPositionForJetTheme: function () {
            if (this.body.hasClass(this.classes.jet)) {
                this.positions.desktop.at = this.positions.jet.at;
            }
        }
    });

    return $.mage.amShopbyFilterFlyout;
});
