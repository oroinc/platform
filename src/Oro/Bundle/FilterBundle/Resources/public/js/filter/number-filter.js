define(function(require) {
    'use strict';

    var NumberFilter;

    var _ = require('underscore');
    var $ = require('jquery');
    var ChoiceFilter = require('./choice-filter');
    var NumberFormatter = require('orofilter/js/formatter/number-formatter');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');

    /**
     * Number filter: formats value as a number
     */
    NumberFilter = ChoiceFilter.extend({
        /**
         * @property {Boolean}
         */
        wrapHintValue: false,

        /**
         * @inheritDoc
         */
        constructor: function NumberFilter() {
            NumberFilter.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize.
         *
         * @param {Object} options
         * @param {*} [options.formatter] Object with methods fromRaw and toRaw or
         *      a string name of formatter (e.g. "integer", "decimal")
         */
        initialize: function(options) {
            _.defaults(this, {
                formatterOptions: {},
                arraySeparator: ',',
                arrayOperators: [],
                dataType: 'data_integer'
            });

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
                value = this.formatter.toRaw(value);
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
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(data) {
            this._initInputWidget();

            return NumberFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @returns {boolean}
         * @private
         */
        _isValid: function() {
            var rawValue = this.formatter.toRaw(this._readDOMValue().value);
            var validValue = rawValue === void 0 || this._checkNumberRules(rawValue);

            if (!validValue) {
                return false;
            } else {
                return NumberFilter.__super__._isValid.apply(this, arguments);
            }
        },

        /**
         *
         * @param value
         * @returns {boolean}
         * @private
         */
        _checkNumberRules: function(value) {
            if (_.isUndefined(value)) {
                return true;
            }

            var result = true;

            if (!_.isNumber(value) || _.isNaN(value)) {
                this._showNumberWarning();
                result = false;
            }

            if (this.formatter.percent && value > 100) {
                this._showMaxPercentWarning();
                result = false;
            }

            return result;
        },

        /**
         * @private
         */
        _showNumberWarning: function() {
            mediator.execute(
                'showFlashMessage',
                'warning',
                __('oro.form.number.nan')
            );
        },

        /**
         * @private
         */
        _showMaxPercentWarning: function() {
            mediator.execute(
                'showFlashMessage',
                'warning',
                __('This value should be {{ limit }} or less.', {limit: 100})
            );
        },

        _initInputWidget: function() {
            if (this.formatterOptions.decimals) {
                _.each(this.$el.find('input[type="number"]:not([data-precision])'), function(field) {
                    $(field).attr('data-precision', this.formatterOptions.decimals);
                }, this);
            }

            this.$el.inputWidget('seekAndCreate');
        }
    });

    return NumberFilter;
});
