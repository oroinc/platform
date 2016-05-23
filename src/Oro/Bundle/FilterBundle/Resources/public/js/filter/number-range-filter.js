define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oro/filter/number-filter'
], function($, _, __, tools, NumberFilter) {
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
        _applyValueAndHideCriteria: function() {
            this._beforeApply();
            NumberRangeFilter.__super__._applyValueAndHideCriteria.apply(this);
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
            var oldValue = tools.deepClone(value);
            if (this.isApplicable(value.type)) {
                if (value.value && value.value_end) {
                    //if both values are filled
                    //start/end values if end value is lower than start
                    if (value.value_end < value.value) {
                        var endValue = value.value_end;
                        value.value_end = value.value;
                        value.value = endValue;
                    }
                } else {
                    if (value.value || value.value_end) {
                        //if only one value is filled, replace filter type to less than or more than
                        if (value.value_end) {
                            value.type = value.type == this.typeValues.between ?
                                this.fallbackTypeValues.lessThan : this.fallbackTypeValues.moreThan;
                            value.value = value.value_end;
                            value.value_end = '';
                        } else {
                            value.type = value.type == this.typeValues.between ?
                                this.fallbackTypeValues.moreThan : this.fallbackTypeValues.lessThan;
                        }
                    }
                }
                if (!tools.isEqualsLoosely(value, oldValue)) {
                    //apply new values and filter type
                    this._writeDOMValue(value);
                }
            }
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(data) {
            this._setInputValue(this.criteriaValueSelectors.value_end, data.value_end);
            var $typeInput = this.$(this.criteriaValueSelectors.type);
            if ($typeInput.length && data.type != $typeInput.val()) {
                this._setInputValue(this.criteriaValueSelectors.type, data.type);
                this._updateTypeDropdown(data.type);
            }

            return NumberRangeFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * Update type dropdown with new type value
         *
         * @param {string} value
         * @protected
         */
        _updateTypeDropdown: function(value) {
            var a = this.$('.dropdown-menu:eq(0) a').filter(function() {
                return $(this).data('value') == value
            });
            a.parent().parent().find('li').each(function() {
                $(this).removeClass('active');
            });
            a.parent().addClass('active');

            var parentDiv = a.parent().parent().parent();
            var choiceName = a.html();
            choiceName += this.caret;
            parentDiv.find('.dropdown-toggle').html(choiceName);
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
