define(function (require) {
    'use strict';
    var _ = require('underscore');

    /**
     * Accepts tree of date variables and helps to manage them
     *
     * Kind of:
     * {
     *     "value": {
     *         "1": "now",
     *         "2": "today",
     *         "3": "start of the week",
     *         "4": "start of the month",
     *         "5": "start of the quarter",
     *         "6": "start of the year",
     *         "10": "current day",
     *         "11": "current week",
     *         "12": "current month",
     *         "13": "current quarter",
     *         "14": "current year",
     *         "15": "first day of quarter",
     *         "16": "first month of quarter"
     *     },
     *     "dayofweek": {
     *         "10": "current day",
     *         "15": "first day of quarter"
     *     },
     *     "week": {
     *         "11": "current week"
     *     },
     *     "day": {
     *         "10": "current day",
     *         "15": "first day of quarter"
     *     },
     *     "month": {
     *         "12": "current month",
     *         "16": "first month of quarter"
     *     },
     *     "quarter": {
     *         "13": "current quarter"
     *     },
     *     "dayofyear": {
     *         "10": "current day",
     *         "15": "first day of quarter"
     *     },
     *     "year": {
     *         "14": "current year"
     *     }
     * };
     *
     * @param {Object.<Object>} dateVariables
     * @constructor
     */
    function DateVariableHelper(dateVariables) {
        var variables;
        this.dateVariables = dateVariables;
        // plain object with variable id => variable title
        variables = _.toArray(this.dateVariables);
        variables.unshift({});
        this.index = _.extend.apply(_, variables);
    }

    DateVariableHelper.prototype = {
        /**
         * Check if value is a date-variable
         *
         * @param {string} value
         * @returns {boolean}
         */
        isDateVariable: function (value) {
            var result;
            result = _.some(this.index, function (displayValue, index) {
                var rawValue = '{{' + index + '}}';
                return rawValue === value.substr(0, rawValue.length) ||
                    displayValue === value.substr(0, displayValue.length);
            });
            return result;
        },

        /**
         * Converts Raw value to Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatDisplayValue: function (value) {
            for (var i in this.index) {
                if (this.index.hasOwnProperty(i)) {
                    value = value.replace(new RegExp('\{+' + i + '\}+', 'gi'), this.index[i]);
                }
            }
            return value;
        },

        /**
         * Converts Raw value to Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatRawValue: function (value) {
            for (var i in this.index) {
                if (this.index.hasOwnProperty(i)) {
                    value = value.replace(new RegExp(this.index[i], 'g'), '{{' + i +'}}');
                }
            }
            return value;
        }
    };

    return DateVariableHelper;
});
