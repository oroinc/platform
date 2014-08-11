/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './choice-filter',
    'orofilter/js/formatter/number-formatter'
], function (_, ChoiceFilter, NumberFormatter) {
    'use strict';

    var NumberFilter;

    /**
     * Number filter: formats value as a number
     *
     * @export  oro/filter/number-filter
     * @class   oro.filter.NumberFilter
     * @extends oro.filter.ChoiceFilter
     */
    NumberFilter = ChoiceFilter.extend({
        /**
         * Initialize.
         *
         * @param {Object} options
         * @param {*} [options.formatter] Object with methods fromRaw and toRaw or
         *      a string name of formatter (e.g. "integer", "decimal")
         */
        initialize: function (options) {
            options = options || {};
            // init formatter options if it was not initialized so far
            if (_.isUndefined(this.formatterOptions)) {
                this.formatterOptions = {};
            }
            this.formatter = new NumberFormatter(this.formatterOptions);
            NumberFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function (value) {
            if (value.value === '') {
                value.value = undefined;
            } else {
                value.value = this.formatter.toRaw(String(value.value));
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _formatDisplayValue: function (value) {
            if (_.isNumber(value.value)) {
                value.value = this.formatter.fromRaw(value.value);
            }
            return value;
        }
    });

    return NumberFilter;
});
