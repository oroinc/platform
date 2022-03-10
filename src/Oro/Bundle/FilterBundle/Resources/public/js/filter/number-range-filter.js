define(function(require) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/number-range-filter.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const NumberFilter = require('oro/filter/number-filter');

    const NumberRangeFilter = NumberFilter.extend({

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        template: template,
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
            between: 7,
            notBetween: 8
        },

        /**
         * Type values to use if only one value is selected
         *
         * @property {Object}
         */
        fallbackTypeValues: {
            moreThan: 2,
            lessThan: 6
        },

        /**
         * Flag to allow filter type change if start or end value is missing
         *
         * @property
         */
        autoUpdateRangeFilterType: true,

        /**
         * @inheritdoc
         */
        constructor: function NumberRangeFilter(options) {
            NumberRangeFilter.__super__.constructor.call(this, options);
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

            NumberRangeFilter.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        isEmptyValue: function() {
            if (!this.isApplicable(this.value.type)) {
                return NumberRangeFilter.__super__.isEmptyValue.call(this);
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
         * @inheritdoc
         */
        _applyValueAndHideCriteria: function() {
            this._beforeApply();
            NumberRangeFilter.__super__._applyValueAndHideCriteria.call(this);
        },

        /**
         * @inheritdoc
         */
        _updateValueField: function() {
            NumberRangeFilter.__super__._updateValueField.call(this);

            const type = this.$(this.criteriaValueSelectors.type).val();
            const filterEnd = this.$('.filter-separator, .filter-end');
            const {inputFieldAriaLabel, rangeStartFieldAriaLabel} = this.getTemplateDataProps();

            if (this.isApplicable(type)) {
                this.$(this.criteriaValueSelectors.value).attr('aria-label', rangeStartFieldAriaLabel);
                filterEnd.show();
            } else {
                this.$(this.criteriaValueSelectors.value).attr('aria-label', inputFieldAriaLabel);
                filterEnd.hide();

                this.value.value_end = this.emptyValue.value_end;
                this._setInputValue(this.criteriaValueSelectors.value_end, this.value.value_end);
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
            let hint = '';

            let option = this._getChoiceOption(type);

            if (start && end) {
                option = this._getChoiceOption(type);
                hint += [option.label, start, __('and'), end].join(' ');
            } else if (between && start || !between && end) {
                hint += [__('oro.filter.number_range.greater_than'), start || end].join(' ');
            } else if (between && end || !between && start) {
                hint += [__('oro.filter.number_range.less_than'), end || start].join(' ');
            }

            return hint;
        },

        /**
         * @inheritdoc
         */
        _getCriteriaHint: function(...args) {
            if (this.isEmptyValue()) {
                return this.placeholder;
            }

            let hint = '';
            const data = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();
            if (data.value || data.value_end) {
                const type = data.type ? data.type.toString() : '';
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
                hint = NumberRangeFilter.__super__._getCriteriaHint.apply(this, args);
            }

            return hint ? hint : this.placeholder;
        },

        /**
         * Called before filter value is applied by user action
         *
         * @protected
         */
        _beforeApply: function() {
            if (this.autoUpdateRangeFilterType) {
                this._updateRangeFilter(this._readDOMValue());
            }
        },

        /**
         * Apply additional logic for "between" filters
         * - Swap start/end values if end value is lower than start
         * - Change filter type to more than/less than, when only one value is filled
         *
         * @param {*} value
         * @protected
         */
        _updateRangeFilter: function(value) {
            value = this._formatRawValue(value);
            const oldValue = tools.deepClone(value);
            value = this.swapValues(value);
            if (!tools.isEqualsLoosely(value, oldValue)) {
                // apply new values and filter type
                this.setValue(value);
            }
        },

        /**
         * @inheritdoc
         */
        _writeDOMValue: function(data) {
            NumberRangeFilter.__super__._writeDOMValue.call(this, data);

            this._setInputValue(this.criteriaValueSelectors.value_end, data.value_end);
            const $typeInput = this.$(this.criteriaValueSelectors.type);
            if ($typeInput.length && data.type !== $typeInput.val()) {
                this._setInputValue(this.criteriaValueSelectors.type, data.type);
                this._updateTypeDropdown(data.type);
            }

            return this;
        },

        /**
         * Update type dropdown with new type value
         *
         * @param {string} value
         * @protected
         */
        _updateTypeDropdown: function(value) {
            const a = this.$('.dropdown-menu:eq(0) a').filter(function() {
                return $(this).data('value') === value;
            });
            a.parent().parent().find('li').each(function() {
                $(this).removeClass('active');
            });
            a.parent().addClass('active');

            const parentDiv = a.parent().parent().parent();
            let choiceName = a.html();
            choiceName += this.caret;
            parentDiv.find('[data-toggle="dropdown"]').html(choiceName);
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            const data = NumberRangeFilter.__super__._readDOMValue.call(this);

            data.value_end = this._getInputValue(this.criteriaValueSelectors.value_end);

            return data;
        },

        swapValues(data) {
            if (!this.isApplicable(data.type)) {
                return data;
            }
            if (data.value && data.value_end) {
                // if both values are filled
                // start/end values if end value is lower than start
                if (data.value_end < data.value) {
                    const endValue = data.value_end;
                    data.value_end = data.value;
                    data.value = endValue;
                }
            } else {
                if (data.value || data.value_end) {
                    const type = parseInt(data.type);
                    // if only one value is filled, replace filter type to less than or more than
                    if (data.value_end) {
                        data.type = type === this.typeValues.between
                            ? this.fallbackTypeValues.lessThan : this.fallbackTypeValues.moreThan;
                        data.value = data.value_end;
                        data.value_end = '';
                    } else {
                        data.type = type === this.typeValues.between
                            ? this.fallbackTypeValues.moreThan : this.fallbackTypeValues.lessThan;
                    }
                }
            }

            return data;
        },

        /**
         * @inheritdoc
         */
        _formatRawValue: function(data) {
            const formatted = NumberRangeFilter.__super__._formatRawValue.call(this, data);

            formatted.value_end = this._toRawValue(data.value_end);

            return formatted;
        },

        /**
         * @inheritdoc
         */
        _formatDisplayValue: function(data) {
            const formatted = NumberRangeFilter.__super__._formatDisplayValue.call(this, data);

            formatted.value_end = this._toDisplayValue(data.value_end);

            return formatted;
        },

        /**
         * @inheritdoc
         * @returns {boolean}
         * @private
         */
        _isValid: function() {
            const rawValue = this.formatter.toRaw(this._readDOMValue().value_end);
            const validValueEnd = rawValue === void 0 || this._checkNumberRules(rawValue);

            if (!validValueEnd) {
                return false;
            } else {
                return NumberRangeFilter.__super__._isValid.call(this);
            }
        },

        getTemplateDataProps() {
            const data = NumberRangeFilter.__super__.getTemplateDataProps.call(this);

            return {
                ...data,
                rangeStartFieldAriaLabel: __('oro.filter.range_fields.start_field.aria_label', {
                    label: this.label
                }),
                rangeEndFieldAriaLabel: __('oro.filter.range_fields.end_field.aria_label', {
                    label: this.label
                })
            };
        }
    });

    return NumberRangeFilter;
});
