define([
    'underscore',
    './choice-filter',
    'orofilter/js/formatter/number-formatter'
], function(_, ChoiceFilter, NumberFormatter) {
    'use strict';

    var NumberFilter;

    /**
     * Number filter: formats value as a number
     */
    NumberFilter = ChoiceFilter.extend({
        /**
         * @property {Boolean}
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
            if (_.isUndefined(this.arraySeparator)) {
                this.arraySeparator = ',';
            }
            if (_.isUndefined(this.arrayOperators)) {
                this.arrayOperators = [];
            }
            if (_.isUndefined(this.dataType)) {
                this.dataType = 'data_integer';
            }
            this._filterArrayChoices();
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

        _filterArrayChoices: function() {
            this.choices = _.filter(
                this.choices,
                _.bind(function(item) {
                    return this.dataType === 'data_integer' || !this._isArrayType(item.data);
                }, this)
            );
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
            }

            if (value !== undefined && this._isArrayTypeSelected()) {
                return this._formatArray(value);
            }

            if (value !== undefined) {
                value = this.formatter.toRaw(String(value));
            }
            return value;
        },

        /**
         * @param {*} value
         * @return {*}
         */
        _toDisplayValue: function(value) {
            if (value) {
                if (this._isArrayTypeSelected()) {
                    return this._formatArray(value);
                } else if (_.isString(value)) {
                    value = parseFloat(value);
                }
            }

            if (_.isNumber(value)) {
                value = this.formatter.fromRaw(value);
            }
            return value;
        },

        /**
         * @param {*} value
         * @return {String}
         */
        _formatArray: function(value) {
            return _.filter(
                _.map(
                    value.toString().split(this.arraySeparator),
                    function(number) {
                        return parseInt(number);
                    }
                ),
                function(number) {
                    return !isNaN(number);
                }
            ).join(this.arraySeparator);
        },

        /**
         * @return {Boolean}
         */
        _isArrayTypeSelected: function() {
            return this._isArrayType(this._readDOMValue().type);
        },

        /**
         * @return {Boolean}
         */
        _isArrayType: function(type) {
            return _.contains(this.arrayOperators, parseInt(type) || 0);
        }
    });

    return NumberFilter;
});
