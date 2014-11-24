/*global define*/
define([], function () {
    'use strict';

    /**
     * @export oroui/js/tools/color-util
     * @name   oroui.colorUtil
     */
    return {
        /**
         * Converts a hex string to an RGB object
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {Object|null}
         */
        hex2rgb: function (hex) {
            var result = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        /**
         * Converts an RGB object to a hex string
         *
         * @param {Object} rgb An RGB object
         * @returns {string}
         */
        rgb2hex: function (rgb) {
            var filter = function (dec) {
                var hex = dec.toString(16).toUpperCase();
                return hex.length === 1 ? '0' + hex : hex;
            };
            return '#' + filter(rgb.r) + filter(rgb.g) + filter(rgb.b);
        },

        /**
         * Calculates contrast color
         * @see http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {string} Calculated sufficient contrast color, black or white.
         *                   If the given color is invalid or cannot be parsed, returns black.
         */
        getContrastColor: function (hex) {
            var rgb = this.hex2rgb(hex),
                yiq = rgb ? ((299 * rgb.r + 587 * rgb.g + 114 * rgb.b) / 1000) : 255,
                clrDiff = rgb ? (rgb.r + rgb.g + rgb.b) : 1000;
            return yiq > 125 && clrDiff > 500 ? '#000000' : '#FFFFFF';
        }
    };
});
