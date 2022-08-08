define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const ChoiceFilter = require('oro/filter/choice-filter');
    const NumberFormatter = require('orofilter/js/formatter/number-formatter');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');

    /**
     * Number filter: formats value as a number
     */
    const NumberFilter = ChoiceFilter.extend({
        /**
         * @property {Boolean}
         */
        wrapHintValue: false,

        /**
         * @inheritdoc
         */
        constructor: function NumberFilter(options) {
            NumberFilter.__super__.constructor.call(this, options);
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
                dataType: 'data_integer',
                limitDecimals: false
            });

            this._filterArrayChoices();
            this.formatter = new NumberFormatter(this.formatterOptions);
            NumberFilter.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
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
                item => {
                    return this.dataType === 'data_integer' || !this._isArrayType(item.data);
                }
            );
        },

        /**
         * @inheritdoc
         */
        _formatRawValue: function(value) {
            const formatted = _.clone(value);

            formatted.value = this._toRawValue(value.value);

            return formatted;
        },

        /**
         * @inheritdoc
         */
        _formatDisplayValue: function(value) {
            const formatted = _.clone(value);

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
                }
            }

            return this.formatter.fromRaw(value);
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
                        return parseFloat(number);
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
         * @inheritdoc
         */
        _writeDOMValue: function(data) {
            this._initInputWidget();

            return NumberFilter.__super__._writeDOMValue.call(this, data);
        },

        /**
         * @inheritdoc
         * @returns {boolean}
         * @private
         */
        _isValid: function() {
            const rawValue = this.formatter.toRaw(this._readDOMValue().value);
            const validValue = rawValue === void 0 || this._checkNumberRules(rawValue);

            if (!validValue) {
                return false;
            } else {
                return NumberFilter.__super__._isValid.call(this);
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

            let result = true;

            if (_.isNaN(value)) {
                this._showNumberWarning();
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

        _initInputWidget: function() {
            _.each(this.$el.find('input[type="number"]'), function(field) {
                if (this.formatter.decimals) {
                    $(field).attr('data-precision', this.formatter.decimals);
                }
                $(field).attr('data-limit-decimals', this.limitDecimals);
            }, this);

            this.$el.inputWidget('seekAndCreate');
        }
    });

    return NumberFilter;
});
