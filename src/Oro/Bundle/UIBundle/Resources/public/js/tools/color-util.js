define([], function() {
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
        hex2rgb: function(hex) {
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
        rgb2hex: function(rgb) {
            var filter = function(dec) {
                var hex = dec.toString(16).toUpperCase();
                return hex.length === 1 ? '0' + hex : hex;
            };
            return '#' + filter(rgb.r) + filter(rgb.g) + filter(rgb.b);
        },

        colorDifference: function(x, y) {
            return Math.abs(x.r - y.r) * 299 +
                    Math.abs(x.g - y.g) * 587 +
                    Math.abs(x.b - y.b) * 114;
        },

        /**
         * Calculates contrast color
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {string} Calculated sufficient contrast color, black or white.
         *                   If the given color is invalid or cannot be parsed, returns black.
         */
        getContrastColor: function(hex, blackPreference) {
            var rgb = this.hex2rgb(hex);
            var white = {r: 255, g: 255, b: 255};
            var black = {r: 0, g: 0, b: 0};
            if (!blackPreference) {
                blackPreference = 0.58;
            }
            if (!rgb) {
                return '#000000';
            }
            return (this.colorDifference(rgb, black) * blackPreference > this.colorDifference(rgb, white)) ?
                '#000000' : '#FFFFFF';
        }
    };
});
