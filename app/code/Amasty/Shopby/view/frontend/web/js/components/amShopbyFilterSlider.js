/**
 * Price filter Slider
 */

define([
    'jquery',
    'jquery-ui-modules/slider',
    'mage/tooltip',
    'amShopbyFiltersSync'
], function ($) {
    'use strict';

    $.widget('mage.amShopbyFilterSlider', $.mage.amShopbyFilterAbstract, {
        options: {},
        selectors: {
            value: '[data-amshopby-slider-id="value"]',
            slider: '[data-amshopby-slider-id="slider"]',
            display: '[data-amshopby-slider-id="display"]',
            container: '[data-am-js="slider-container"]',
            sliderTooltip: '[data-amshopby-js="slider-tooltip"]',
            sliderHandle: '.ui-slider-handle'
        },
        classes: {
            tooltip: 'amshopby-slider-tooltip',
            styleDefault: '-default',
            loaded: '-loaded'
        },
        attributes: {
            tooltip: 'slider-tooltip'
        },
        slider: null,
        value: null,
        display: null,

        /**
         * inheritDoc
         *
         * @private
         */
        _create: function () {
            var fromLabel = Number(this._getInitialFromTo('from')).toFixed(2),
                toLabel = Number(this._getInitialFromTo('to')).toFixed(2);

            this.setCurrency(this.options.curRate);
            this.initNodes();

            if (this.options.to) {
                this.value.val(fromLabel + '-' + toLabel);
            } else {
                this.value.trigger('change');
                this.value.trigger('sync');
            }

            fromLabel = this.processPrice(false, fromLabel, this.options.deltaFrom);
            toLabel = this.processPrice(false, toLabel, this.options.deltaTo);

            this.initSlider(fromLabel, toLabel);
            this.renderLabel(fromLabel, toLabel);
            this.setTooltipValue(this.slider, fromLabel, toLabel);
            this.value.on('amshopby:sync_change', this.onSyncChange.bind(this));

            if (this.options.hideDisplay) {
                this.display.hide();
            }
        },

        /**
         * @private
         * @param {string} value - 'from' or 'to'
         * @returns {string | number}
         */
        _getInitialFromTo: function (value) {
            switch (value) {
                case 'from':
                    return this.options.from && this.options.from >= this.options.min
                        ? this.options.from
                        : this.options.min;
                case 'to':
                    return this.options.to && this.options.to <= this.options.max
                        ? this.options.to
                        : this.options.max;
            }
        },

        /**
         * @public
         * @returns {void}
         */
        initNodes: function () {
            this.value = this.element.find(this.selectors.value);
            this.slider = this.element.find(this.selectors.slider);
            this.display = this.element.find(this.selectors.display);
        },

        /**
         * @public
         * @param {number} fromLabel
         * @param {number} toLabel
         * @returns {void}
         */
        initSlider: function (fromLabel, toLabel) {
            this.slider.slider({
                step: (this.options.step ? this.options.step : 1) * this.options.curRate,
                range: true,
                min: this.options.min * this.options.curRate,
                max: this.options.max * this.options.curRate,
                values: [fromLabel, toLabel],
                slide: this.onSlide.bind(this),
                change: this.onChange.bind(this),
                create: this.onCreate.bind(this)
            });
        },

        /**
         * @public
         * @returns {boolean}
         */
        isNotDefaultSlider: function () {
            return this.options.sliderStyle !== this.classes.styleDefault;
        },

        /**
         * @public
         * @param {object} event
         * @param {object} ui
         * @returns {boolean}
         */
        onChange: function (event, ui) {
            var rate;

            if (this.slider.skipOnChange !== true) {
                rate = $(ui.handle).closest(this.selectors.container).data('rate');

                this.setValue(Number(ui.values[0]).toFixed(2), Number(ui.values[1]).toFixed(2),true, rate);
            }

            return true;
        },

        /**
         * @public
         * @param {object} event
         * @param {object} ui
         * @returns {boolean}
         */
        onSlide: function (event, ui) {
            var valueFrom = ui.values[0],
                valueTo = ui.values[1];

            this.setValue(valueFrom, valueTo, false);
            this.renderLabel(valueFrom, valueTo);

            this.setTooltipValue(event.target, valueFrom, valueTo);

            return true;
        },

        /**
         * @public
         * @param {object} event
         * @returns {void}
         */
        onCreate: function (event) {
            if (this.isNotDefaultSlider()) {
                this.renderTooltips(event);
            }

            this.slider.addClass(this.classes.loaded);
        },

        /**
         * @public
         * @param {object} event
         * @param {array} values
         * @returns {void}
         */
        onSyncChange: function (event, values) {
            var value = values[0].split('-'),
                valueFrom,
                valueTo;

            if (value.length === 2) {
                valueFrom = this.parseValue(value[0]);
                valueTo = this.parseValue(value[1]);

                this.slider.skipOnChange = true;

                this.slider.slider('values', [valueFrom, valueTo]);
                this.setValueWithoutChange(valueFrom, valueTo);
                this.setTooltipValue(this.slider, valueFrom, valueTo);
                this.slider.skipOnChange = false;
            }
        },

        /**
         * @public
         * @param {number} from
         * @param {number} to
         * @param {boolean} apply
         * @returns {void}
         */
        setValue: function (from, to, apply) {
            var valueFrom = this.parseValue(this.processPrice(true, from), 2),
                valueTo = this.parseValue(this.processPrice(true, to), 2),
                newValue,
                changedValue,
                linkHref;

            newValue = valueFrom + '-' + valueTo;
            changedValue = this.value.val() !== newValue;

            this.value.val(newValue);

            if (!this.isBaseCurrency()) {
                this.setDeltaParams(this.getDeltaParams(from, valueFrom, to, valueTo, false));
            }

            if (changedValue) {
                this.value.trigger('change');
                this.value.trigger('sync');
            }

            if (apply !== false) {
                newValue = valueFrom + '-' + valueTo;
                linkHref = this.options.url
                    .replace('amshopby_slider_from', valueFrom)
                    .replace('amshopby_slider_to', valueTo);

                linkHref = this.getUrlWithDelta(
                    linkHref,
                    valueFrom,
                    from,
                    valueTo,
                    to,
                    this.options.deltaFrom,
                    this.options.deltaTo
                );

                this.value.val(newValue);
                $.mage.amShopbyFilterAbstract.prototype.renderShowButton(0, this.element[0]);
                $.mage.amShopbyFilterAbstract.prototype.apply(linkHref);
            }
        },

        /**
         * @public
         * @param {number} from
         * @param {number} to
         * @returns {void}
         */
        setValueWithoutChange: function (from, to) {
            this.value.val(this.parseValue(from) + '-' + this.parseValue(to));
        },

        /**
         * @public
         * @param {string} from
         * @param {string} to
         * @returns {string}
         */
        getLabel: function (from, to) {
            return this.options.template.replace('{from}', from.toString()).replace('{to}', to.toString());
        },

        /**
         * @public
         * @param {number} from
         * @param {number} to
         * @returns {void}
         */
        renderLabel: function (from, to) {
            var valueFrom = this.parseValue(from),
                valueTo = this.parseValue(to);

            this.display.html(this.getLabel(valueFrom, valueTo));
        },

        /**
         * @public
         * @returns {object}
         */
        getTooltip: function () {
            return $('<span>', {
                'class': this.classes.tooltip,
                'data-amshopby-js': this.attributes.tooltip
            });
        },

        /**
         * @public
         * @param {object} event
         * @returns {void}
         */
        renderTooltips: function (event) {
            $(event.target).find(this.selectors.sliderHandle).prepend(this.getTooltip());
        },

        /**
         * @public
         * @param {object} element
         * @param {string} from
         * @param {string} to
         * @returns {void}
         */
        setTooltipValue: function (element, from, to) {
            var handle = this.selectors.sliderHandle,
                tooltip = this.selectors.sliderTooltip,
                currencySymbol = this.options.currencySymbol,
                currencyPosition = parseInt(this.options.currencyPosition),
                valueFrom = this.parseValue(from),
                valueTo = this.parseValue(to),
                firstElement = $(element).find(handle + ':first-of-type ' + tooltip),
                lastElement = $(element).find(handle + ':last-of-type ' + tooltip);

            if (!this.isNotDefaultSlider()) {
                return;
            }

            if (currencyPosition) {
                firstElement.html(valueFrom + currencySymbol);
                lastElement.html(valueTo + currencySymbol);
            } else {
                firstElement.html(currencySymbol + valueFrom);
                lastElement.html(currencySymbol + valueTo);
            }
        },

        /**
         * @public
         * @returns {number}
         */
        getFixedValue: function () {
            return this.getSignsCount(this.options.step, 0);
        },

        /**
         * @public
         * @param {string | number} value
         * @param {number} toFixedValue
         * @returns {string}
         */
        parseValue: function (value, toFixedValue) {
            return parseFloat(value).toFixed(toFixedValue ? toFixedValue : this.getFixedValue());
        },

        /**
         * @public
         * @param {string} value
         * @returns {string}
         */
        replacePriceDelimiter: function (value) {
            return value.replace('.', ',');
        }
    });

    return $.mage.amShopbyFilterSlider;
});
