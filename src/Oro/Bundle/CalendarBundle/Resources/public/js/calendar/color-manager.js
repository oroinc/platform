/* global define */
define(['underscore'],
function(_) {
    'use strict';

    /**
     * @export  oro/calendar/color-manager
     * @class   oro.calendar.ColorManager
     */
    var ColorManager = {
        /**
         * A list of text/background colors are used to determine colors of events of connected calendars
         *  @property {Array}
         */
        colors: [
            ['FFFFFF', 'AC725E'], ['FFFFFF', 'D06B64'], ['FFFFFF', 'F83A22'], ['000000', 'FA573C'],
            ['000000', 'FF7537'], ['000000', 'FFAD46'], ['000000', '42D692'], ['FFFFFF', '16A765'],
            ['000000', '7BD148'], ['000000', 'B3DC6C'], ['000000', 'FBE983'], ['000000', 'FAD165'],
            ['000000', '92E1C0'], ['000000', '9FE1E7'], ['000000', '9FC6E7'], ['FFFFFF', '4986E7'],
            ['000000', '9A9CFF'], ['000000', 'B99AFF'], ['000000', 'C2C2C2'], ['000000', 'CABDBF'],
            ['000000', 'CCA6AC'], ['000000', 'F691B2'], ['FFFFFF', 'CD74E6'], ['FFFFFF', 'A47AE2']
        ],

        /** @property {Object} */
        defaultColors: null,

        /** @property {Object} */
        calendarColors: null,

        initialize: function() {
            this.defaultColors = this.findColors('4986E7');
            this.calendarColors = {};
        },

        setCalendarColors: function (calendarId, color, backgroundColor) {
            this.calendarColors[calendarId] = {
                color: '#' + color,
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
                obj.color = colors[0];
                obj.backgroundColor = colors[1];
            } else if (_.isEmpty(obj.color)) {
                obj.color = this.defaultColors[0];
            } else if (_.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this.defaultColors[1];
            }
        },

        findColors: function (bgColor) {
            if (_.isEmpty(bgColor)) {
                return this.findColors(this.defaultColors[1]);
            }
            bgColor = bgColor.toUpperCase();
            var result = _.find(this.colors, function(item) { return item[1] === bgColor; });
            if (_.isUndefined(result)) {
                result = this.findColors(this.defaultColors[1]);
            }
            return result;
        },

        findNextColors: function (bgColor) {
            if (_.isEmpty(bgColor)) {
                return this.findColors(this.defaultColors[1]);
            }
            bgColor = bgColor.toUpperCase();
            var i = -1;
            _.each(this.colors, function(item, index) {
                if (item[1] === bgColor) {
                    i = index;
                }
            });
            if (i === -1) {
                return this.findColors(this.defaultColors[1]);
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
    }
});
