define([
    'jquery',
    'Magento_Swatches/js/form/element/swatch-visual',
], function ($, Swatch) {

    return Swatch.extend({
        defaults: {
            swatchValue: '',
            swatchPath: ''
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().observe(['swatchValue']);

            return this;
        },

        /**
         * EXTEND Initialize wrapped former implementation.
         * Check if value is hex or link. If link then wrap it in url()
         *
         * @returns {Object} Chainable.
         */
        initOldCode: function () {
            if (this.value().indexOf('#') !== 0
              && this.value().indexOf('url(') !== 0
              && this.value() !== '') {
                this.swatchValue('url("' + this.swatchPath + this.value() + '")');
            } else {
                this.swatchValue(this.value());
            }

            this._super();

            return this;
        },

        /**
         * EXTEND Handler function that execute when color changes.
         * Clear background before swatch change
         *
         * @param {String} data - color
         */
        onChangeColor: function (data) {
            $('[name="' + this.inputName + '"]').next('.swatch_window').css('background', '');

            this._super();
        },

        /**
         * Configure data scope.
         */
        configureDataScope: function () {
            var recordId, prefixName;

            // Get recordId
            recordId = this.parentName.split(/\D/).filter(Boolean).join('-');
            prefixName = this.dataScopeToHtmlArray(this.prefixName);

            this.elementName = this.prefixElementName + recordId;
            this.inputName = prefixName + '[' + this.elementName + ']';
            this.exportDataLink = 'data.' + this.prefixName + '.' + this.elementName;
            this.exports.value = this.provider + ':' + this.exportDataLink;
        },

        destroy: function (skipUpdate) {
            $('[name="upload_iframe_' + this.elementName + '"]').remove();
            $('[name="swatch_form_image_upload_' + this.elementName + '"]').remove();

            this._super(skipUpdate);
        }
    });
});
