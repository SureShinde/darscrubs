/**
 *  Amasty From To Filter
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/slider',
    'mage/tooltip',
    'mage/validation',
    'mage/translate',
    'Amasty_Shopby/js/jquery.ui.touch-punch.min',
    'Amasty_ShopbyBase/js/chosen/chosen.jquery',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterFromTo', $.mage.amShopbyFilterAbstract, {
        selectors: {
            dataFromTo: '[data-amshopby-fromto="{mode}"]',
            range: '.range'
        },
        classes: {
          range: 'range'
        },
        from: null,
        to: null,
        value: null,
        timer: null,
        go: null,
        skip: false,

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            var self = this,
                dataFromTo = this.selectors.dataFromTo,
                fromValue,
                toValue,
                newValue;

            this.setCurrency(this.options.curRate);

            this.value = this.element.find(dataFromTo.replace('{mode}', 'value'));
            this.from = this.element.find(dataFromTo.replace('{mode}', 'from'));
            this.to = this.element.find(dataFromTo.replace('{mode}', 'to'));
            this.go = this.element.find(dataFromTo.replace('{mode}', 'go'));

            fromValue = this._getInitialRange('from');
            toValue = this._getInitialRange('to');

            this.options.min = this.options.min * this.options.curRate;
            this.options.max = this.options.max * this.options.curRate;

            this.value.on('amshopby:sync_change', self.onSyncChange.bind(this));

            newValue = fromValue + '-' + toValue;

            this.value.trigger('amshopby:sync_change', [[self.value.val() ? self.value.val() : newValue, true]]);

            if (this.go.length > 0) {
                this.go.on('click', self.applyFilter.bind(this));
            }

            this.changeEvent(this.from, self.onChange.bind(this));
            this.changeEvent(this.to, self.onChange.bind(this));
            this.formValidate();
        },

        /**
         * @private
         * @param {string} value - 'from' or 'to'
         * @returns {string | number}
         */
        _getInitialRange: function (value) {
            return this.options[value] ? this.options[value] : (value === 'from' ? this.options.min : this.options.max);
        },

        /**
         * @public
         * @return {void}
         */
        formValidate: function () {
            var self = this,
                message = $.mage.__('Please enter a valid price range.'),
                parent;

            self.element.find('form').mage('validation', {
                errorPlacement: function (error, element) {
                    parent = element.parent();

                    if (parent.hasClass(self.classes.range)) {
                        parent.find(self.errorElement + '.' + self.errorClass).remove().end().append(error);
                    } else {
                        error.insertAfter(element);
                    }
                },
                messages: {
                    'am_shopby_filter_widget_attr_price_from': {
                        'greater-than-equals-to': message,
                        'validate-digits-range': message
                    },
                    'am_shopby_filter_widget_attr_price_to': {
                        'greater-than-equals-to': message,
                        'validate-digits-range': message
                    }
                }
            });
        },

        /**
         * @public
         * @param {Object} event
         * @return {void}
         */
        onChange: function (event) {
            var to = this.to.val() ? this.to.val() : this.options.max,
                from = this.from.val() ? this.from.val() : this.options.min,
                fixed = this.getFixed(this.isSlider(), this.isPrice()),
                fromToInterval = this.checkFromTo(parseFloat(from).toFixed(fixed), parseFloat(to).toFixed(fixed)),
                oldVal = this.value.val(),
                oldValValues = oldVal.split('-'),
                newVal = fromToInterval.from.toFixed(fixed) + '-' + fromToInterval.to.toFixed(fixed),
                changed = !((fromToInterval.from === Number(oldValValues[0]))
                    && (fromToInterval.to === Number(oldValValues[1])));

            this.value.val(newVal);

            if (changed) {
                newVal = fromToInterval.from.toFixed(fixed) + '-' + fromToInterval.to.toFixed(fixed);

                this.value.val(newVal);
                this.value.trigger('change');
                this.value.trigger('sync');

                if (this.go.length === 0) {
                    this.renderShowButton(event, this.element[0]);
                    this.applyFilter();
                }
            }
        },

        /**
         * @public
         * @param {Object} event
         * @return {void}
         */
        applyFilter: function (event) {
            var valueFrom = this.processPrice(true, this.from.val()),
                valueTo = this.processPrice(true, this.to.val()),
                fromToInterval = this.checkFromTo(valueFrom, valueTo),
                linkHref = this.options.url
                    .replace('amshopby_slider_from', fromToInterval.from.toFixed(2))
                    .replace('amshopby_slider_to', fromToInterval.to.toFixed(2));

            linkHref = this.getUrlWithDelta(
                linkHref,
                valueFrom,
                this.from.val(),
                valueTo,
                this.to.val(),
                this.options.deltaFrom,
                this.options.deltaTo
            );

            if (!this.isBaseCurrency()) {
                this.setDeltaParams(this.getDeltaParams(this.from.val(), valueFrom, this.to.val(), valueTo, false));
            }

            this.apply(linkHref);

            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
        },

        /**
         * @public
         * @param {Object} event
         * @param {Array} values
         * @return {void}
         */
        onSyncChange: function (event, values) {
            var value = values[0].split('-'),
                fixed = this.getFixed(this.isSlider(), 0),
                max = Number(this.options.max).toFixed(fixed),
                min = Number(this.options.min).toFixed(fixed),
                to = max,
                from = min,
                i;

            for (i = 0; i < value.length; i++) {
                value[i] = this.processPrice(
                    false,
                    value[i],
                    $.mage.amShopbyFilterAbstract.prototype.options[i === 0 ? 'deltaFrom' : 'deltaTo']
                );
            }

            if (value.length === 2 && (value[0] || value[1])) {
                from = value[0] === '' ? 0 : parseFloat(value[0]).toFixed(fixed);
                to = (value[1] === 0 || value[1] === '') ? this.options.max : parseFloat(value[1]).toFixed(fixed);

                if (this.isDropDown()) {
                    to = Math.ceil(to);
                }
            }

            this.element.find(this.selectors.dataFromTo.replace('{mode}', 'from')).val(from);
            this.element.find(this.selectors.dataFromTo.replace('{mode}', 'to')).val(to);
        },

        /**
         * @public
         * @param {Number | String} from
         * @param {Number | String} to
         * @return {Object}
         */
        checkFromTo: function (from, to) {
            var interval = {},
                fromOld;

            from = parseFloat(from);
            to = parseFloat(to);

            interval.from = from < this.options.min ? this.options.min : from;
            interval.from = interval.from > this.options.max ? this.options.min : interval.from;
            interval.to = to > this.options.max ? this.options.max : to;
            interval.to = interval.to < this.options.min ? this.options.max : interval.to;

            if (parseFloat(interval.from) > parseFloat(interval.to)) {
                fromOld = interval.from;

                interval.from = interval.to;
                interval.to = fromOld;
            }

            interval.from = Number(interval.from);
            interval.to = Number(interval.to);

            return interval;
        },

        /**
         * trigger keyup on input with delay
         * @param input
         * @param callback
         */
        changeEvent: function (input, callback) {
            input.on('keyup', function (event) {
                if (this.timer !== null) {
                    clearTimeout(this.timer);
                }

                if (this.go.length === 0) {
                    this.timer = setTimeout(callback(event), 1000);
                } else {
                    callback(event);
                }
            }.bind(this));
        },

        /**
         * @public
         * @return {Boolean}
         */
        isSlider: function () {
            return (typeof this.options.isSlider !== 'undefined' && this.options.isSlider);
        },

        /**
         * @public
         * @return {Boolean}
         */
        isDropDown: function () {
            return (typeof this.options.isDropdown !== 'undefined' && this.options.isDropdown);
        }
    });

    return $.mage.amShopbyFilterFromTo;
});
