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

        // Convert XYZ to LAB
        xyz2lab: function (color) {
            var x = color.x, y = color.y, z = color.z;
            var ref_X =  95.047;
            var ref_Y = 100.000;
            var ref_Z = 108.883;

            var _X = x / ref_X;
            var _Y = y / ref_Y;
            var _Z = z / ref_Z;

            if (_X > 0.008856) {
                _X = Math.pow(_X, (1/3));
            }
            else {
                _X = (7.787 * _X) + (16 / 116);
            }

            if (_Y > 0.008856) {
                _Y = Math.pow(_Y, (1/3));
            }
            else {
                _Y = (7.787 * _Y) + (16 / 116);
            }

            if (_Z > 0.008856) {
                _Z = Math.pow(_Z, (1/3));
            }
            else {
                _Z = (7.787 * _Z) + (16 / 116);
            }

            var CIE_L = (116 * _Y) - 16;
            var CIE_a = 500 * (_X - _Y);
            var CIE_b = 200 * (_Y - _Z);

            return {l: CIE_L, a:CIE_a, b: CIE_b};
        },

        // Convert RGB to XYZ
        rgb2xyz: function (color) {
            var r = color.r,
                g = color.g,
                b = color.b;
            var _r = (r / 255);
            var _g = (g / 255);
            var _b = (b / 255);

            if (_r > 0.04045) {
                _r = Math.pow(((_r + 0.055) / 1.055), 2.4);
            }
            else {
                _r = _r / 12.92;
            }

            if (_g > 0.04045) {
                _g = Math.pow(((_g + 0.055) / 1.055), 2.4);
            }
            else {
                _g = _g / 12.92;
            }

            if (_b > 0.04045) {
                _b = Math.pow(((_b + 0.055) / 1.055), 2.4);
            }
            else {
                _b = _b / 12.92;
            }

            _r = _r * 100;
            _g = _g * 100;
            _b = _b * 100;

            var X = _r * 0.4124 + _g * 0.3576 + _b * 0.1805;
            var Y = _r * 0.2126 + _g * 0.7152 + _b * 0.0722;
            var Z = _r * 0.0193 + _g * 0.1192 + _b * 0.9505;

            return {x:X, y:Y, z:Z};
        },

        // Convert RGB to LAB
        rgb2lab: function (color) {
            return this.xyz2lab(this.rgb2xyz(color));
        },

        // Convert HEX to LAB
        hex2lab: function (color) {
            return this.xyz2lab(this.rgb2xyz(this.hex2rgb(color)));
        },

        // color difference calculation by cie1994 standard
        colorDifference: function (x, y, isTextiles) {
            var k2;
            var k1;
            var kl;
            var kh = 1;
            var kc = 1;
            if (isTextiles) {
                k2 = 0.014;
                k1 = 0.048;
                kl = 2;
            }
            else {
                k2 = 0.015;
                k1 = 0.045;
                kl = 1;
            }

            var c1 = Math.sqrt(x.a * x.a + x.b * x.b);
            var c2 = Math.sqrt(y.a * y.a + y.b * y.b);

            var sh = 1 + k2 * c1;
            var sc = 1 + k1 * c1;
            var sl = 1;

            var da = x.a - y.a;
            var db = x.b - y.b;
            var dc = c1 - c2;

            var dl = x.l - y.l;
            var dh = Math.sqrt((da * da) + (db * db) - (dc * dc)) || 0;

            return Math.sqrt(Math.pow((dl/(kl * sl)),2) + Math.pow((dc/(kc * sc)),2) + Math.pow((dh/(kh * sh)),2));
        },

        /**
         * Calculates contrast color
         * @see http://en.wikipedia.org/wiki/Color_difference
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {string} Calculated sufficient contrast color, black or white.
         *                   If the given color is invalid or cannot be parsed, returns black.
         */
        getContrastColor: function (hex, blackPreference) {
            var lab = this.hex2lab(hex),
                white = {
                    a: 0.00526049995830391,
                    b: -0.010408184525267927,
                    l: 100
                },
                black = {l: 0, a: 0, b: 0};
            return (this.colorDifference(lab, black) > this.colorDifference(lab, white)) ? '#000000' : '#FFFFFF';
        }
    };
});
