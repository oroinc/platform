define(function(require) {
    'use strict';

    var DateFilter;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var __ = require('orotranslation/js/translator');
    var ChoiceFilter = require('./choice-filter');
    var DatePickerView = require('oroui/js/app/views/datepicker/datepicker-view');
    var VariableDatePickerView = require('orofilter/js/app/views/datepicker/variable-datepicker-view');
    var DateVariableHelper = require('orofilter/js/date-variable-helper');
    var DateValueHelper = require('orofilter/js/date-value-helper');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var localeSettings = require('orolocale/js/locale-settings');
    var layout = require('oroui/js/layout');

    require('orofilter/js/datevariables-widget');

    /**
     * Date filter: filter type as option + interval begin and end dates
     */
    DateFilter = ChoiceFilter.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#date-filter-template',

        /**
         * Template selector for date field parts
         *
         * @property
         */
        fieldTemplateSelector: '#select-field-template',

        /**
         * Template selector for dropdown container
         *
         * @property
         */
        dropdownTemplateSelector: '#date-filter-dropdown-template',

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select',// to handle both type and part changes
            date_type: 'select[name][name!=date_part]',
            date_part: 'select[name=date_part]',
            value: {
                start: 'input[name="start"]',
                end:   'input[name="end"]'
            }
        },

        /**
         * CSS class for visual date input elements
         *
         * @property
         */
        inputClass: 'date-visual-element',

        /**
         * Date widget options
         *
         * @property
         */
        dateWidgetOptions: {
            changeMonth: true,
            changeYear:  true,
            yearRange:  '-80:+1',
            dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'),
            altFormat:  'yy-mm-dd',
            className:  'date-filter-widget',
            showButtonPanel: true
        },

        /**
         * View constructor for picker element
         *
         * @property
         */
        picker: tools.isMobile() ? DatePickerView : VariableDatePickerView,

        /**
         * Additional date widget options that might be passed to filter
         * http://api.jqueryui.com/datepicker/
         *
         * @property
         */
        externalWidgetOptions: {},

        /**
         * Date filter type values
         *
         * @property
         */
        typeValues: {
            between:    1,
            notBetween: 2,
            moreThan:   3,
            lessThan:   4,
            equal:      5,
            notEqual:   6
        },

        /**
         * @property
         */
        typeDefinedValues: {
            today:        7,
            this_week:    8,
            this_month:   9,
            this_quarter: 10,
            this_year:    11,
            all_time:     12
        },

        /**
         * @property
         */
        dateParts: [],

        /**
         * Date parts
         *
         * @property
         */
        datePartTooltips: {
            week: 'oro.filter.date.part.week.tooltip',
            day: 'oro.filter.date.part.day.tooltip',
            quarter: 'oro.filter.date.part.quarter.tooltip',
            dayofyear: 'oro.filter.date.part.dayofyear.tooltip',
            year:  'oro.filter.date.part.year.tooltip'
        },

        hasPartsElement: false,

        /**
         * List of acceptable day formats
         * @type {Array.<string>}
         */
        dayFormats: null,

        events: {
            'change select': 'onChangeFilterType'
        },

        /**
         * Flag to allow filter type change if start or end date is missing
         *
         * @property
         */
        autoUpdateRangeFilterType: true,

        /**
         * @param {Object} options
         * @param {Array.<string>=} options.dayFormats List of acceptable day formats
         * @inheritDoc
         */
        initialize: function(options) {
            this.dayFormats = options && options.dayFormats || [datetimeFormatter.getDayFormat()];
            // make own copy of options
            this.dateWidgetOptions = $.extend(true, {}, this.dateWidgetOptions, this.externalWidgetOptions);
            this.dateVariableHelper = new DateVariableHelper(this.dateWidgetOptions.dateVars);
            this.dateValueHelper = new DateValueHelper(this.dayFormats.slice());

            //parts rendered only if theme exist
            this.hasPartsElement = (this.templateTheme !== '');

            // init empty value object if it was not initialized so far
            if (_.isUndefined(this.emptyValue)) {
                this.emptyValue = {
                    type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value),
                    part: 'value',
                    value: {
                        start: '',
                        end: ''
                    }
                };
            }

            if (_.isUndefined(this.dateParts)) {
                this.dateParts = [];
            }
            // temp code to keep backward compatible
            if ($.isPlainObject(this.dateParts)) {
                this.dateParts = _.map(this.dateParts, function(option, i) {
                    var value = i.toString();

                    return {
                        value: value,
                        label: option,
                        tooltip: this._getPartTooltip(value)
                    };
                }, this);
            }

            if (_.isUndefined(this.emptyPart)) {
                var firstPart = _.first(this.dateParts).value;
                this.emptyPart = {
                    type: (_.isEmpty(this.dateParts) ? '' : firstPart),
                    value: firstPart
                };
            }

            DateFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.dateParts;
            delete this.emptyPart;
            delete this.emptyValue;
            DateFilter.__super__.dispose.call(this);
        },

        onChangeFilterType: function(e) {
            var select = this.$el.find(e.currentTarget);
            var value = select.val();
            this.changeFilterType(value);
        },

        /**
         * @inheritDoc
         */
        _applyValueAndHideCriteria: function() {
            this._beforeApply();
            DateFilter.__super__._applyValueAndHideCriteria.apply(this);
        },

        changeFilterType: function(value) {
            var type = parseInt(value, 10);
            if (!isNaN(type)) {
                // it's type
                this.$('.filter-separator, .filter-start-date, .filter-end-date').css('display', '');
                var typeDefinedValues = [
                    this.typeDefinedValues.today,
                    this.typeDefinedValues.this_week,
                    this.typeDefinedValues.this_month,
                    this.typeDefinedValues.this_quarter,
                    this.typeDefinedValues.this_year,
                    this.typeDefinedValues.all_time,
                ];
                if (typeDefinedValues.indexOf(type) > -1) {
                    this.$('.filter-separator, .filter-start-date, .filter-end-date').hide();
                    this.subview('start').setValue('');
                    this.subview('end').setValue('');
                } else if (this.typeValues.moreThan === type) {
                    this.$('.filter-separator, .filter-end-date').hide();
                    this.subview('end').setValue('');
                } else if (this.typeValues.lessThan === type) {
                    this.$('.filter-separator, .filter-start-date').hide();
                    this.subview('start').setValue('');
                } else if (this.typeValues.equal === type) {
                    this.$('.filter-separator, .filter-end-date').hide();
                    this.subview('end').setValue('');
                } else if (this.typeValues.notEqual === type) {
                    this.$('.filter-separator, .filter-start-date').hide();
                    this.subview('start').setValue('');
                }

                this.$(this.criteriaValueSelectors.date_type)
                 .closest('.dropdown')
                 .find('.dropdown-toggle')
                 .html(this.$(this.criteriaValueSelectors.date_type + ' :selected').text());
            } else {
                // it's part
                this.subview('start').setPart(value);
                this.subview('start').setValue('');
                this.subview('end').setPart(value);
                this.subview('end').setValue('');

                this.$(this.criteriaValueSelectors.date_part)
                    .closest('.dropdown')
                    .find('.dropdown-toggle')
                    .attr('title', this._getPartTooltip(value));
            }
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            var value = _.extend({}, this.emptyValue, this.getValue());
            var part = {value: value.part, type: value.part};

            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var selectedPartLabel = this._getSelectedChoiceLabel('dateParts', part);
            this.dateWidgetOptions.part = part.type;

            var datePartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];

            // add date parts only if embed template used
            if (this.templateTheme !== '') {
                parts.push(
                    datePartTemplate({
                        name: this.name + '_part',
                        choices: this.dateParts,
                        selectedChoice: value.part,
                        selectedChoiceLabel: selectedPartLabel,
                        selectedChoiceTooltip: this._getPartTooltip(value.part)
                    })
                );
            }

            this._updateRangeFilter(value, false);

            parts.push(
                datePartTemplate({
                    name: this.name,
                    choices: this.choices,
                    selectedChoice: value.type,
                    selectedChoiceLabel: selectedChoiceLabel,
                    popoverContent: __('oro.filter.date.info')
                })
            );

            var displayValue = this._formatDisplayValue(value);
            var $filter = $(
                this.template({
                    inputClass: this.inputClass,
                    value: displayValue,
                    parts: parts,
                    popoverContent: __('oro.filter.date.info')
                })
            );

            this._appendFilter($filter);
            this.$(this.criteriaSelector).attr('tabindex', '0');

            this._renderSubViews();
            this.changeFilterType(value.type);
            layout.initPopover(this.$el);

            if (value) {
                this._updateTooltipVisibility(value.part);
            }
            this.on('update', _.bind(function() {
                var value = this.getValue();
                if (value) {
                    this._updateTooltipVisibility(value.part);
                }
            }, this));

            this._criteriaRenderd = true;
        },

        /**
         * Renders picker views for both start and end values
         *
         * @protected
         */
        _renderSubViews: function() {
            var name;
            var selector;
            var pickerView;
            var options;
            var value = this.criteriaValueSelectors.value;
            for (name in value) {
                if (!value.hasOwnProperty(name)) {
                    continue;
                }
                selector = value[name];
                options = this._getPickerConfigurationOptions({
                    el: this.$(selector)
                });
                pickerView = new this.picker(options);
                this.subview(name, pickerView);
            }
        },

        _updateValueField: function() {
            // nothing to do
        },

        /**
         * Prepares configuration options for picker view
         *
         * @param {Object} options
         * @returns {Object}
         * @protected
         */
        _getPickerConfigurationOptions: function(options) {
            _.extend(options, {
                nativeMode: tools.isMobile(),
                dateInputAttrs: {
                    'class': 'datepicker-input ' + this.inputClass,
                    'placeholder': __('oro.form.choose_date')
                },
                datePickerOptions: this.dateWidgetOptions,
                dropdownTemplate: this._getTemplate(this.dropdownTemplateSelector),
                backendFormat: datetimeFormatter.getDateFormat(),
                dayFormats: this.dayFormats.slice()
            });
            return options;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var hint = '';
            var option;
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            if (value.value) {
                var start = value.value.start;
                var end = value.value.end;
                var type = value.type ? value.type.toString() : '';

                switch (type) {
                    case this.typeValues.moreThan.toString():
                        hint += [__('more than'), start].join(' ');
                        break;
                    case this.typeValues.lessThan.toString():
                        hint += [__('less than'), end].join(' ');
                        break;
                    case this.typeValues.notBetween.toString():
                        if (start && end) {
                            option = this._getChoiceOption(this.typeValues.notBetween);
                            hint += [option.label, start, __('and'), end].join(' ');
                        } else if (start) {
                            hint += [__('before'), start].join(' ');
                        } else if (end) {
                            hint += [__('after'), end].join(' ');
                        }
                        break;
                    default:
                        if (start && end) {
                            option = this._getChoiceOption(this.typeValues.between);
                            hint += [option.label, start, __('and'), end].join(' ');
                        } else if (start) {
                            hint += [__('from'), start].join(' ');
                        } else if (end) {
                            hint += [__('to'), end].join(' ');
                        }
                        break;
                }
                if (hint) {
                    return hint;
                }
            }

            return this.placeholder;
        },

        /**
         * @inheritDoc
         */
        _formatDisplayValue: function(value) {
            if (value.value && value.value.start) {
                value.value.start = this._toDisplayValue(value.value.start, value.part);
            }
            if (value.value && value.value.end) {
                value.value.end = this._toDisplayValue(value.value.end, value.part);
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function(value) {
            if (value.value && value.value.start) {
                value.value.start = this._toRawValue(value.value.start, value.part);
            }
            if (value.value && value.value.end) {
                value.value.end = this._toRawValue(value.value.end, value.part);
            }
            return value;
        },

        /**
         * Called before filter value is applied by user action
         *
         * @protected
         */
        _beforeApply: function() {
            if (this.autoUpdateRangeFilterType) {
                this._updateRangeFilter(this._readDOMValue(), true);
            }
        },

        /**
         * Apply additional logic for "between" filters
         * - Swap start and end dates if end date is behind start date
         * - Change filter type to more than/less than, when only one date is filled
         *
         * @param {*} value
         * @param {boolean} updateDom
         * @protected
         */
        _updateRangeFilter: function(value, updateDom) {
            var oldValue = tools.deepClone(value);
            var type = parseInt(value.type);
            if (value.value &&
                (type === this.typeValues.between || type === this.typeValues.notBetween)) {
                if (value.value.start && value.value.end) {
                    //if both dates are filled
                    if (!this.dateVariableHelper.isDateVariable(value.value.end) &&
                        !this.dateVariableHelper.isDateVariable(value.value.start)) {
                        //swap end/start date if no variables are used and end date is behind start date
                        var end = datetimeFormatter.getMomentForBackendDateTime(value.value.end);
                        var start = datetimeFormatter.getMomentForBackendDateTime(value.value.start);
                        if (end < start) {
                            var endValue = value.value.end;
                            value.value.end = value.value.start;
                            value.value.start = endValue;
                        }
                    }
                } else {
                    if (value.value.start || value.value.end) {
                        //if only one date is filled replace filter type to less than or more than
                        if (type === this.typeValues.between) {
                            value.type = value.value.end ? this.typeValues.lessThan : this.typeValues.moreThan;
                        } else if (type === this.typeValues.notBetween) {
                            if (!value.value.end) {
                                //less than type expects end date
                                value.type = this.typeValues.lessThan;
                                value.value.end = value.value.start;
                                value.value.start = '';
                            } else {
                                //more than type expects start date
                                value.type = this.typeValues.moreThan;
                                value.value.start = value.value.end;
                                value.value.end = '';
                            }
                        }
                    }
                }
                if (!tools.isEqualsLoosely(value, oldValue)) {
                    //apply new values and filter type
                    this.value = tools.deepClone(value);
                    if (updateDom) {
                        this._writeDOMValue(value);
                    }
                }
            }
        },

        /**
         * Converts the date value from Raw to Display
         *
         * @param {string} value
         * @param {string} part
         * @returns {string}
         * @protected
         */
        _toDisplayValue: function(value, part) {
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else if (part === 'value' && this.dateValueHelper.isValid(value)) {
                value = this.dateValueHelper.formatDisplayValue(value);
            } else if (datetimeFormatter.isBackendDateValid(value)) {
                value = datetimeFormatter.formatDate(value);
            }
            return value;
        },

        /**
         * Converts the date value from Display to Raw         *
         * @param {string} value
         * @param {string} part
         * @returns {string}
         * @protected
         */
        _toRawValue: function(value, part) {
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else if (part === 'value' && this.dateValueHelper.isValid(value)) {
                value = this.dateValueHelper.formatRawValue(value);
            } else if (datetimeFormatter.isDateValid(value)) {
                value = datetimeFormatter.convertDateToBackendFormat(value);
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (!tools.isEqualsLoosely(newValue, oldValue)) {
                var start = this.subview('start');
                var end = this.subview('end');
                if (start && start.updateFront) {
                    start.updateFront();
                }
                if (end && end.updateFront) {
                    end.updateFront();
                }
                this.trigger('update');
            }
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            var $typeInput;
            this._setInputValue(this.criteriaValueSelectors.value.start, value.value.start);
            this._setInputValue(this.criteriaValueSelectors.value.end, value.value.end);
            $typeInput = this.$(this.criteriaValueSelectors.date_type);
            if ($typeInput.val() !== value.type) {
                $typeInput.val(value.type).trigger('change');
            }
            if (value.part) {
                this._setInputValue(this.criteriaValueSelectors.date_part, value.part);
            }
            return this;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            _.result(this.subview('start'), 'checkConsistency');
            _.result(this.subview('end'), 'checkConsistency');

            return {
                type: this._getInputValue(this.criteriaValueSelectors.date_type),
                //empty default parts value if parts not exist
                part: this.hasPartsElement ? this._getInputValue(this.criteriaValueSelectors.date_part) : 'value',
                value: {
                    start: this._getInputValue(this.criteriaValueSelectors.value.start),
                    end:   this._getInputValue(this.criteriaValueSelectors.value.end)
                }
            };
        },

        /**
         * @inheritDoc
         */
        _focusCriteria: function() {
            this.$(this.criteriaSelector).focus();
        },

        _getSelectedChoiceLabel: function(property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function(choice) {
                    return (String(choice.value) === String(value.type));
                });
                selectedChoiceLabel = foundChoice.label;
            }

            return selectedChoiceLabel;
        },

        _getPartTooltip: function(part) {
            return this.datePartTooltips[part] ? __(this.datePartTooltips[part]) : null;
        },

        _updateTooltipVisibility: function(part) {
            if (part === 'value') {
                this.$('.field-condition-date-popover').removeClass('hide');
            } else {
                this.$('.field-condition-date-popover').addClass('hide');
            }
        }
    });

    return DateFilter;
});
