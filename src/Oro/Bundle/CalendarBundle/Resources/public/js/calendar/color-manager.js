/*global define*/
define(['underscore'], function (_) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/color-manager
     * @class   orocalendar.calendar.colorManager
     */
    var ColorManager = {
        /**
         * A list of text/background colors are used to determine colors of events of connected calendars
         *  @property {Array}
         */
        colors: [
            'AC725E', 'D06B64', 'F83A22', 'FA573C', 'FF7537', 'FFAD46', '42D692', '16A765',
            '7BD148', 'B3DC6C', 'FBE983', 'FAD165', '92E1C0', '9FE1E7', '9FC6E7', '4986E7',
            '9A9CFF', 'B99AFF', 'C2C2C2', 'CABDBF', 'CCA6AC', 'F691B2', 'CD74E6', 'A47AE2'
        ],

        /** @property {String} */
        defaultColor: null,

        /** @property {Object} */
        calendarColors: null,

        initialize: function () {
            this.defaultColor = this.findColors('4986E7');
            this.calendarColors = {};
        },

        setCalendarColors: function (calendarId, backgroundColor) {
            this.calendarColors[calendarId] = {
                color: '#' + this.getColor(backgroundColor),
                backgroundColor: '#' + backgroundColor
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
                var colors = this.findNextColors(getLastBackgroundColor());
                obj.backgroundColor = colors;
            } else if (_.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this.defaultColor;
            }
            obj.color = this.getColor(obj.backgroundColor);
        },

        findColors: function (bgColor) {
            if (_.isEmpty(bgColor)) {
                return this.findColors(this.defaultColor);
            }
            bgColor = bgColor.toUpperCase();
            var result = _.find(this.colors, function (item) { return item === bgColor; });
            if (_.isUndefined(result)) {
                result = this.findColors(this.defaultColor);
            }
            return result;
        },

        findNextColors: function (bgColor) {
            if (_.isEmpty(bgColor)) {
                return this.findColors(this.defaultColor);
            }
            bgColor = bgColor.toUpperCase();
            var i = -1;
            _.each(this.colors, function (item, index) {
                if (item === bgColor) {
                    i = index;
                }
            });
            if (i === -1) {
                return this.findColors(this.defaultColor);
            }
            if ((i + 1) === _.size(this.colors)) {
                return _.first(this.colors);
            }
            return this.colors[i + 1];
        },

        hex2rgb: function (hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        rgb2hex: function (r, g, b) {
            var filter = function(dec) {
                var hex = dec.toString(16).toUpperCase();
                return hex.length == 1 ? '0' + hex : hex;
            }
            return filter(r) + filter(g) + filter(b)
        },

        getColor: function(color) {
            var color = this.hex2rgb(color);
            var d = 0;
            var a = 1 - (0.299 * color.r + 0.587 * color.g + 0.114 * color.b) / 255;
            if (a < 0.5) {
                d = 0;
            } else {
                d = 255;
            }
            return this.rgb2hex(d, d, d);
}
    };

    return function () {
        var obj = _.extend({}, ColorManager);
        obj.initialize();
        return obj;
    };
});
