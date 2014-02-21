/* global define */
define(['underscore', 'oro/app'],
function(_, app) {
    'use strict';

    /**
     * A set of utility functions for the query designer
     *
     * @export oroquerydesigner/js/query-designer/util
     * @name oro.queryDesigner.util
     */
    return {
        /**
         * Checks whether the given value conforms to at least one condition from applicable argument or not
         *
         * @param {Array}  applicable A list of applicable conditions
         * @param {Object} value      A value to test
         * @return {Boolean} The matched applicable condition or undefined if the value is not applicable
         */
        isApplicable: function(applicable, value) {
            var matched = this.matchApplicable(applicable, value);
            return !_.isUndefined(matched);
        },

        /**
         * Checks if the given value conforms to at least one condition from applicable argument
         *
         * @param {Array}  applicable A list of applicable conditions
         * @param {Object} value      A value to test
         * @return {Object} The matched applicable condition or undefined if the value is not applicable
         */
        matchApplicable: function(applicable, value) {
            return _.find(applicable, function(item) {
                var res = true;
                _.each(item, function (val, key) {
                    if (!_.has(value, key) || !app.isEqualsLoosely(val, value[key])) {
                        res = false;
                    }
                });
                return res;
            });
        },

        /**
         * Removes all options, except 'empty' one, from the given SELECT element
         *
         * @param {jQuery} $el SELECT element
         */
        clearSelect: function ($el) {
            var emptyItem = $el.find('option[value=""]');
            $el.empty();
            if (emptyItem.length > 0) {
                $el.append(emptyItem.get(0));
            }
        },

        /**
         * Searches OPTION element by its value
         *
         * @param {jQuery} $el SELECT element
         * @param {String} value The option value to find
         * @returns {jQuery} OPTION element, can return empty jQuery object if the option was not found
         */
        findSelectOption: function ($el, value) {
            return $el.find('option[value="' + value.replace(/\\/g,"\\\\").replace(/:/g,"\\:") + '"]');
        }
    }
});
