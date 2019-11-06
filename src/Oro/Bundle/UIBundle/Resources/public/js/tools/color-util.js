define(function(require) {
    'use strict';

    const _ = require('underscore');

    /**
     * @export oroui/js/tools/color-util
     * @name   oroui.colorUtil
     */
    return {
        DARK: '#000',

        LIGHT: '#FFF',

        configure: function(options) {
            Object.assign(this, _.pick(options, 'DARK', 'LIGHT'));
        },

        /**
         * Converts a hex string to an RGB object
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {Object|null}
         */
        hex2rgb: function(hex) {
            const result = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
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
            const filter = function(dec) {
                const hex = dec.toString(16).toUpperCase();
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
         * Calculates if the color is closer to dark or to light
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @param {string=} blackPreference
         * @returns {boolean} Calculated sufficient contrast color, black or white.
         *                   If the given color is invalid or cannot be parsed, returns black.
         */
        isDarkColor: function(hex, blackPreference) {
            const rgb = this.hex2rgb(hex);
            const white = {r: 255, g: 255, b: 255};
            const black = {r: 0, g: 0, b: 0};
            if (!blackPreference) {
                blackPreference = 0.58;
            }
            return !rgb || (this.colorDifference(rgb, black) * blackPreference > this.colorDifference(rgb, white));
        },

        /**
         * Calculates contrast color
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @param {string=} blackPreference
         * @returns {string} Calculated sufficient contrast color, black or white.
         *                   If the given color is invalid or cannot be parsed, returns black.
         */
        getContrastColor: function(hex, blackPreference) {
            return this.isDarkColor(hex, blackPreference) ? this.DARK : this.LIGHT;
        }
    };
});
