/**
 * Swiper Slider Init
 */

define([
    'swiper',
    'domReady!'
], function () {
    'use strict';

    return function (config, element) {
        new Swiper(element, config);
    };
});
