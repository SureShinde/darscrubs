/**
 * @api
 */
define([
    'ko',
    'mage/translate',
    'underscore',
    'rjsResolver',
    'Magento_Ui/js/form/element/checkbox-set'
], function (ko, $t, _, resolver, CheckboxSet) {
    'use strict';

    return CheckboxSet.extend({
        defaults: {
            modules: {
                attributeToSelect: 'index = attribute_id',
                attributeToRender: 'index = attribute_options'
            },
            tracks: {
                options: true,
                optionType: true
            },
            attributeOptions: ko.observableArray([]),
            attributeValues: ko.observableArray([]),
            links: {
                "attributeValues": "${ $.provider }:data.attribute_values"
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var self = this;

            this._super();

            this.range = [
                {label: $t('From'), value: '', module: this},
                {label: $t('To'), value: '', module: this}
            ];

            resolver(function () {
                self.attributeToSelect = self.attributeToSelect();
                self.attributeToRender = self.attributeToRender();

                self._initOptionsData();

                self.attributeToRender.options = ko.observableArray(
                    self.attributeOptionsData[self.attributeToSelect.value()].options
                );
                self.attributeToRender.optionType = ko.observable(
                    self.attributeOptionsData[self.attributeToSelect.value()].type
                );

            });

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super()
                .observe({
                    optionsData: [],
                    optionType: ''
                });

            return this;
        },

        /**
         * @public
         * @returns {void}
         */
        setRangeValue: function () {
            var self = this;

            setTimeout(function () {
                self.module.attributeValues(self.module.attributeValues()) // trigger reload data in provider
            }, 0);
        },

        /**
         * @public
         * @param {String | Number} [optionId] - Option Id
         * @returns {Boolean}
         */
        isChecked: function (optionId) {
            return this.attributeOptions.indexOf(optionId) !== -1;
        },

        /**
         * @private
         * @param {String | Number} [value] - Option Id
         * @returns {void}
         */
        _setOptions: function (value) {
            this.attributeToRender.options(this.attributeOptionsData[value].options);
            this.attributeToRender.optionType(this.attributeOptionsData[value].type);
        },

        /**
         * @private
         * @returns {void}
         */
        _initOptionsData: function () {
            var self = this;

            self.attributeToSelect.value.subscribe(function (value) {
                self._setOptions(value);
                self.attributeToRender.value.splice(0);
                self.attributeToRender.attributeValues.splice(0);
            });
        }
    });
});
