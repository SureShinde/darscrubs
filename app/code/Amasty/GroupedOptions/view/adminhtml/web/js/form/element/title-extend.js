/**
 * @api
 */
define([
    'ko',
    'Magento_Ui/js/form/element/abstract'
], function (ko, Abstract) {
    'use strict';

    return Abstract.extend({
        skipUpdate: false,

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            this.parseValue();
            for (var i=0; i < this.storeData.length; i++) {
                this.storeData[i].module = this;
                this.storeData[i].value = this.getStoreValue(this.storeData[i].index);
            }

            return this;
        },

        parseValue: function () {
            var valueJson = this.value(),
                values = [];

            if (valueJson && typeof valueJson !== 'object') {
                try {
                    values = JSON.parse(valueJson);
                } catch (e) {
                    values = valueJson;
                }

                // case if values string
                if (typeof values !== 'object') {
                    values = [values];
                }
            }

            this.skipUpdate = true;
            this.value(values);
            this.skipUpdate = false;
        },

        /**
         * @public
         * @returns {void}
         */
        setTitleValue: function () {
            var self = this,
                groupNames;

            setTimeout(function () {
                groupNames = self.module.value();
                groupNames[self.index] = self.value;
                self.module.value(groupNames);
            }, 0);
        },

        /**
         * @public
         * @param {String | Number} [storeId] - Option Id
         * @returns {String | Number}
         */
        getStoreValue: function (storeId) {
            var values = this.value();

            return values[storeId] ? values[storeId] : '';
        },

        /**
         * @public
         * @returns {void}
         */
        validate: function () {
            var values = this.value(),
                adminStoreValue = this.getStoreValue(0);

            this.skipUpdate = true;
            this.value(adminStoreValue);
            this._super();
            this.value(values);
            this.skipUpdate = false;
        },

        /**
         * @public
         * @returns {void}
         */
        onUpdate: function () {
            if (this.skipUpdate === false) {
                this._super();
            }
        }
    });
});
