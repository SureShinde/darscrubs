/**
 * Magento Swatch Tooltip Init
 */

define([
    'jquery',
    'Magento_Swatches/js/swatch-renderer'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).SwatchRendererTooltip(config);
    };
});
