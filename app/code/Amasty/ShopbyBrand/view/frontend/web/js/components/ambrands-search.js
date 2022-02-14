/**
 * @return widget
 */

define([
    'jquery',
    'underscore'
], function ($, _) {

    $.widget('am.brandsSearch', {
        options: {
            brands: null
        },
        selectors: {
            input: '[data-ambrands-js="input"]',
            livesearch: '[data-ambrands-js="livesearch"]',
            clearButton: '[data-ambrands-js="clear"]'
        },
        classes: {
            active: '-active'
        },
        nodes: {
            resultItem: '<a class="ambrands-item" href="{url}">{content}</a>'
        },

        /**
         * @private
         */
        _create: function () {
            this._initNodes();
            this._initListeners();
        },

        /**
         * @private
         * @return {void}
         */
        _initNodes: function () {
            this.input = this.element.find(this.selectors.input);
            this.livesearch = this.element.find(this.selectors.livesearch);
            this.clearButton = this.element.find(this.selectors.clearButton);
        },

        /**
         * @private
         * @return {void}
         */
        _initListeners: function () {
            this.input.on('keyup', function (event) {
                this.searchBrands(event.target.value);
            }.bind(this));

            this.clearButton.on('click', function () {
                this.clearSearch();
            }.bind(this));
        },

        /**
         * @param element
         * @param state
         * @public
         */
        toggleElement: function (element, state) {
            element.toggleClass(this.classes.active, state);
        },

        /**
         * @param str
         * @public
         */
        searchBrands: function (str) {
            var brands = this.options.brands,
                livesearch = this.livesearch,
                closeButton = this.clearButton,
                foundBrands = {},
                url,
                result;

            str = str.trim().toLowerCase();

            this.toggleElement(closeButton, str.length !== 0);

            if (str.length === 0) {
                this.toggleElement(livesearch, false);

                return;
            }

            for (url in brands) {
                if (brands[url].toLowerCase().indexOf(str) !== -1) {
                    foundBrands[url] = brands[url];
                }
            }

            if (!Object.keys(foundBrands).length) {
                this.toggleElement(livesearch, false);
            } else {
                result = '';

                for (url in foundBrands) {
                    result += this.nodes.resultItem
                        .replace('{url}', url)
                        .replace('{content}', foundBrands[url]);
                }

                this.toggleElement(livesearch, true);
                livesearch.html(result);
            }
        },

        /**
         * @public
         * @return {void}
         */
        clearSearch: function () {
            this.toggleElement(this.livesearch, false);
            this.toggleElement(this.clearButton, false);
            this.input.val('');
            this.livesearch.html('');
        },
    });

    return $.am.brandsSearch;
});
