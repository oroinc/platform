define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/number-filter'
], function($, _, __, NumberFilter) {
    'use strict';

    var NumberRangeFilter;

    NumberRangeFilter = NumberFilter.extend({

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#number-range-filter-template',

        /**
         * Selectors for filter criteria elements
         *
         * @property {Object}
         */
        criteriaValueSelectors: {
            value_end: 'input[name="value_end"]'
        },

        /**
         * Type values
         *
         * @property {Object}
         */
        typeValues: {
            between:    7,
            notBetween: 8
        },

        /**
         * Initialize.
         */
        initialize: function(options) {
            this.emptyValue = _.defaults(this.emptyValue || {}, {
                type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value),
                value: '',
                value_end: ''
            });

            _.defaults(this.criteriaValueSelectors, NumberRangeFilter.__super__.criteriaValueSelectors);

            NumberRangeFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        isEmptyValue: function() {
            if (!this.isApplicable(this.value.type)) {
                return NumberRangeFilter.__super__.isEmptyValue.apply(this, arguments);
            } else if (!_.has(this.value, 'value') && !_.has(this.value, 'value_end')) {
                return true;
            } else if (this.emptyValue.value === this.value.value &&
                    this.emptyValue.value_end === this.value.value_end) {
                return true;
            } else {
                return false;
            }
        },

        /**
         * @inheritDoc
         */
        _updateValueField: function() {
            NumberRangeFilter.__super__._updateValueField.apply(this, arguments);

            var type = this.$(this.criteriaValueSelectors.type).val();

            if (this.isApplicable(type)) {
                this.$('.filter-separator, .filter-end').show();
            } else {
                this.$('.filter-separator, .filter-end').hide();
                this._setInputValue(this.criteriaValueSelectors.value_end, this.emptyValue.value_end);
            }
        },

        /*
         * @param {Number} type
         * @returns {Boolean}
         */
        isApplicable: function(type) {
            return _.has(_.invert(this.typeValues), type);
        },

        /*
         * @param {Number} type
         * @param {Number} start
         * @param {Number} end
         * @param {Boolean} between
         * @returns {String}
         */
        getRangeHint: function(type, start, end, between) {
            var hint = '';

            var option = this._getChoiceOption(type);

            if (start && end) {
                option = this._getChoiceOption(type);
                hint += [option.label, start, __('and'), end].join(' ');
            } else if (between && start || !between && end) {
                hint += [__('after'), start || end].join(' ');
            } else if (between && end || !between && start) {
                hint += [__('before'), end || start].join(' ');
            }

            return hint;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {

            if (this.isEmptyValue()) {
                return this.placeholder;
            }

            var hint = '';
            var data = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            if (data.value || data.value_end) {
                var type = data.type ? data.type.toString() : '';
                switch (type) {
                    case this.typeValues.between.toString():
                        hint = this.getRangeHint(this.typeValues.between, data.value, data.value_end, true);
                        break;
                    case this.typeValues.notBetween.toString():
                        hint = this.getRangeHint(this.typeValues.notBetween, data.value, data.value_end, false);
                        break;
                }
            }
            if (!hint) {
                hint = NumberRangeFilter.__super__._getCriteriaHint.apply(this, arguments);
            }

            return hint ? hint : this.placeholder;
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(data) {
            this._setInputValue(this.criteriaValueSelectors.value_end, data.value_end);

            return NumberRangeFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var data = NumberRangeFilter.__super__._readDOMValue.apply(this, arguments);

            data.value_end = this._getInputValue(this.criteriaValueSelectors.value_end);

            return data;
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function(data) {
            var formatted = NumberRangeFilter.__super__._formatRawValue.apply(this, arguments);

            formatted.value_end = this._toRawValue(data.value_end);
            // change start/end values order if end value is lower than start
            if (formatted.value_end && formatted.value_end < formatted.value) {
                var endValue = formatted.value_end;
                formatted.value_end = formatted.value;
                formatted.value = endValue;
            }

            return formatted;
        },

        /**
         * @inheritDoc
         */
        _formatDisplayValue: function(data) {
            var formatted = NumberRangeFilter.__super__._formatDisplayValue.apply(this, arguments);

            formatted.value_end = this._toDisplayValue(data.value_end);

            return formatted;
        }
    });

    return NumberRangeFilter;
});
