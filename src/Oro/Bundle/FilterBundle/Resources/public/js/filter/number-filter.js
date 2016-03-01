define([
    'underscore',
    './choice-filter',
    'orofilter/js/formatter/number-formatter'
], function(_, ChoiceFilter, NumberFormatter) {
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
         * @property {boolean}
         */
        wrapHintValue: false,

        /**
         * Initialize.
         *
         * @param {Object} options
         * @param {*} [options.formatter] Object with methods fromRaw and toRaw or
         *      a string name of formatter (e.g. "integer", "decimal")
         */
        initialize: function(options) {
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
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.formatter;
            NumberFilter.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function(value) {
            var formatted = _.clone(value);

            formatted.value = this._toRawValue(value.value);

            return formatted;
        },

        /**
         * @inheritDoc
         */
        _formatDisplayValue: function(value) {
            var formatted = _.clone(value);

            formatted.value = this._toDisplayValue(value.value);

            return formatted;
        },

        /**
         * @param {*} value
         * @return {*}
         */
        _toRawValue: function(value) {
            if (value === '') {
                value = undefined;
            } else {
                value = this.formatter.toRaw(String(value));
            }
            return value;
        },

        /**
         * @param {*} value
         * @return {*}
         */
        _toDisplayValue: function(value) {
            if (value && _.isString(value)) {
                value = parseFloat(value);
            }

            if (_.isNumber(value)) {
                value = this.formatter.fromRaw(value);
            }
            return value;
        }
    });

    return NumberFilter;
});
