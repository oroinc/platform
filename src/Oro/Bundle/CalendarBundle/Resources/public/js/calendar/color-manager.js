/*jslint nomen:true*/
/*global define*/
define(['underscore'], function (_) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/color-manager
     * @class   orocalendar.calendar.colorManager
     */
    var ColorManager = {
        /**
         * A list of background colors are used to determine colors of events of connected calendars
         *  @property {Array}
         */
        colors: null,

        /** @property {String} */
        defaultColor: null,

        /** @property {Object} */
        calendarColors: null,

        initialize: function (options) {
            this.colors = options.colors;
            this.defaultColor = options.colors[15];
            this.calendarColors = {};
        },

        setCalendarColors: function (calendarId, backgroundColor) {
            this.calendarColors[calendarId] = {
                color: this._getContrastColor(backgroundColor),
                backgroundColor: backgroundColor
            };
        },

        removeCalendarColors: function (calendarId) {
            if (!_.isUndefined(this.calendarColors[calendarId])) {
                delete this.calendarColors[calendarId];
            }
        },

        getCalendarColors: function (calendarId) {
            return this.calendarColors[calendarId];
        },

        applyColors: function (obj, getLastBackgroundColor) {
            if (_.isEmpty(obj.color) && _.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this._findNextColor(getLastBackgroundColor());
                obj.color = this._getContrastColor(obj.backgroundColor);
            } else if (_.isEmpty(obj.color)) {
                obj.color = this._getContrastColor(this.defaultColor);
            } else if (_.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this.defaultColor;
            }
        },

        _findColor: function (color) {
            if (_.isEmpty(color)) {
                return this._findColor(this.defaultColor);
            }
            color = color.toUpperCase();
            var result = _.find(this.colors, function (clr) { return clr === color; });
            if (_.isUndefined(result)) {
                result = this._findColor(this.defaultColor);
            }
            return result;
        },

        _findNextColor: function (color) {
            if (_.isEmpty(color)) {
                return this._findColor(this.defaultColor);
            }
            color = color.toUpperCase();
            var i = -1;
            _.each(this.colors, function (clr, index) {
                if (clr === color) {
                    i = index;
                }
            });
            if (i === -1) {
                return this._findColor(this.defaultColor);
            }
            if ((i + 1) === _.size(this.colors)) {
                return _.first(this.colors);
            }
            return this.colors[i + 1];
        },

        _hex2rgb: function (hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        _rgb2hex: function (r, g, b) {
            var filter = function (dec) {
                var hex = dec.toString(16).toUpperCase();
                return hex.length === 1 ? '0' + hex : hex;
            };
            return '#' + filter(r) + filter(g) + filter(b);
        },

        /**
         * Calculates contrast color
         * @see http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
         *
         * @param {string} color A color in sixdigit hexadecimal form.
         * @returns {string|null} Calculated sufficient contrast color, currently black or white.
         *                        If the given color is invalid or cannot be parsed, returns black.
         */
        _getContrastColor: function (color) {
            var rgb = this._hex2rgb(color),
                yiq = rgb ? ((299 * rgb.r + 587 * rgb.g + 114 * rgb.b) / 1000) : 255,
                clrDiff = rgb ? (rgb.r + rgb.g + rgb.b) : 0;
            return yiq > 125 && clrDiff > 500 ? this._rgb2hex(0, 0, 0) : this._rgb2hex(255, 255, 255);
        }
    };

    return function (options) {
        var obj = _.extend({}, ColorManager);
        obj.initialize(options);
        return obj;
    };
});
