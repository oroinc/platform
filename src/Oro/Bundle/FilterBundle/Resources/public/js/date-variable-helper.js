define(function(require) {
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

        this.objectIndex = _.chain(_.extend.apply(_, variables))
            .map(function(name, key) {
                return {
                    key: key,
                    name: name
                };
            })
            .sortBy(function(item) {
                return -item.name.length;
            })
            .value();
    }

    DateVariableHelper.prototype = {
        /**
         * Check if value is a date-variable
         *
         * @param {string} value
         * @returns {boolean}
         */
        isDateVariable: function(value) {
            var result;
            var self = this;
            //replace -5 +60 modifiers to '', we need clear variable
            value = value.replace(/( *[\-+]+ *(\d+)?)/, '');

            result = _.some(this.objectIndex, function(item) {
                var regexpVariable = new RegExp('^' + value + '$', 'i');
                var isShortMonth =
                        !/\s+/.test(item.name) &&
                        regexpVariable.test(item.name.substr(0, 3)) &&
                        self.dateVariables.month[item.key];

                return value === '{{' + item.key + '}}' || regexpVariable.test(item.name) || isShortMonth;
            });
            return result;
        },

        /**
         * Converts Raw value to Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatDisplayValue: function(value) {
            return _.reduce(this.objectIndex, function(value, item) {
                return value.replace(new RegExp('\\{+' + item.key + '\\}+', 'gi'), item.name);
            }, value);
        },

        /**
         * Converts Raw value to Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatRawValue: function(value) {
            var displayValue = null;
            _.each(this.objectIndex, function(item) {
                var regexpVariable = new RegExp('^' + value + '$', 'i');
                var isShortMonth =
                    !/\s+/.test(item.name) &&
                    regexpVariable.test(item.name.substr(0, 3)) &&
                    this.dateVariables.month[item.key];
                if (isShortMonth) {
                    displayValue = item.name.substr(0, 3);
                } else {
                    displayValue = item.name;
                }
                value = value.replace(new RegExp(displayValue, 'gi'), '{{' + item.key + '}}');
            }, this);

            return value;
        }
    };

    return DateVariableHelper;
});
