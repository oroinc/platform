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
            this.defaultColor = this._findColor('4986E7');
            this.calendarColors = {};
        },

        setCalendarColors: function (calendarId, backgroundColor) {
            this.calendarColors[calendarId] = {
                color: '#' + colorUtil.getContrastColor(backgroundColor),
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
                obj.backgroundColor = this._findNextColor(getLastBackgroundColor());
                obj.color = colorUtil.getContrastColor(obj.backgroundColor);
            } else if (_.isEmpty(obj.color)) {
                obj.color = colorUtil.getContrastColor(this.defaultColor);
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
        }
    };

    return function () {
        var obj = _.extend({}, ColorManager);
        obj.initialize();
        return obj;
    };
});
