/**
 *  Amasty Filter Dropdown Component
 */

define([
    'jquery',
    'mage/translate',
    'Amasty_Shopby/js/jquery.ui.touch-punch.min',
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($, $t) {
    'use strict';

    $.widget('mage.amShopbyFilterDropdown', $.mage.amShopbyFilterAbstract, {
        options: {
            isMultiselect: false,
            placeholderText: $t('Select Options'),
            selectedOptions: []
        },

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                selectElement = self.element;

            self.processSelectedOptions();

            if (self.options.isMultiselect) {
                selectElement.chosen({
                    width: '100%',
                    placeholder_text: self.options.placeholderText
                });
            } else {
                selectElement.trigger('sync');
            }

            self.initListeners();
        },
        /**
         * @public
         * @return {void}
         */
        initListeners: function () {
            var self = this,
                selectElement = self.element,
                target,
                value;

            selectElement.change(function (event, elem) {
                if (self.options.isMultiselect) {
                    elem = elem ? elem : $(event.target);
                    value = elem.selected ? elem.selected : elem.deselected;
                    target = $(event.target).parent();
                } else {
                    target = selectElement[0];
                    value = selectElement.val();
                }

                selectElement.trigger('sync', [!value]);

                self.renderShowButton(event, target);
                self.apply(selectElement.find('option[value="' + value + '"]').data('url'));
            });
        },

        /**
         * @public
         * @return {void}
         */
        processSelectedOptions: function () {
            var self = this,
                selectedLength,
                i;

            this.options.selectedOptions[this.element[0].name] = [];

            if (this.element[0].selectedOptions && this.element[0].selectedOptions.length) {
                selectedLength = self.element[0].selectedOptions.length;

                for (i = 0; i < selectedLength; i++) {
                    self.options.selectedOptions[self.element[0].name].push(self.element[0].selectedOptions[i].value);
                }
            }
        }
    });

    return $.mage.amShopbyFilterDropdown;
});
