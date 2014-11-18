/*jslint nomen:true*/
/*global define*/
define(['underscore', 'oroui/js/tools/color-util'], function (_, colorUtil) {
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
                color: this.getContrastColor(backgroundColor),
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
                obj.color = this.getContrastColor(obj.backgroundColor);
            } else if (_.isEmpty(obj.color)) {
                obj.color = this.getContrastColor(this.defaultColor);
            } else if (_.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this.defaultColor;
            }
        },

        /**
         * Calculates contrast color
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {string}
         */
        getContrastColor: function (hex) {
            return colorUtil.getContrastColor(hex);
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
        }
    };

    return function (options) {
        var obj = _.extend({}, ColorManager);
        obj.initialize(options);
        return obj;
    };
});
