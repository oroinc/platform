/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DateFilter,
        $ = require('jquery'),
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
        __ = require('orotranslation/js/translator'),
        ChoiceFilter = require('./choice-filter'),
        VariableDatePickerView = require('orofilter/js/app/views/datepicker/variable-datepicker-view'),
        DateVariableHelper = require('orofilter/js/date-variable-helper'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        localeSettings = require('orolocale/js/locale-settings');
    require('orofilter/js/datevariables-widget');

    /**
     * Date filter: filter type as option + interval begin and end dates
     *
     * @export  oro/filter/date-filter
     * @class   oro.filter.DateFilter
     * @extends oro.filter.ChoiceFilter
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
            date_type: 'select[name!=date_part]',
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
        picker: VariableDatePickerView,

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
            lessThan:   4
        },

        /**
         * Date parts
         *
         * @property
         */
        dateParts: [],

        hasPartsElement: false,

        events: {
            'change select': 'onChangeFilterType'
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            // make own copy of options
            this.dateWidgetOptions = $.extend(true, {}, this.dateWidgetOptions, this.externalWidgetOptions);
            this.dateVariableHelper = new DateVariableHelper(this.dateWidgetOptions.dateVars);

            //parts rendered only if theme exist
            this.hasPartsElement = (this.templateTheme != "");

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
                this.dateParts = _.map(this.dateParts, function (option, i) {
                    return {value: i.toString(), label: option};
                });
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
        dispose: function () {
            if (this.disposed) {
                return;
            }
            delete this.dateParts;
            delete this.emptyPart;
            delete this.emptyValue;
            DateFilter.__super__.dispose.call(this);
        },

        onChangeFilterType: function (e) {
            var select = this.$el.find(e.currentTarget),
                value = select.val();
            this.changeFilterType(value);
        },

        changeFilterType: function (value) {
            var type = parseInt(value, 10);
            if (!isNaN(type)) {
                // it's type
                this.$('.filter-separator, .filter-start-date, .filter-end-date').css('display','');
                if (this.typeValues.moreThan === type) {
                    this.$('.filter-separator, .filter-end-date').hide();
                    this.subview('end').setValue('');
                } else if (this.typeValues.lessThan === type) {
                    this.$('.filter-separator, .filter-start-date').hide();
                    this.subview('start').setValue('');
                }
            } else {
                // it's part
                this.subview('start').setPart(value);
                this.subview('start').setValue('');
                this.subview('end').setPart(value);
                this.subview('end').setValue('');
            }
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function () {
            var value = _.extend({}, this.emptyValue, this.getValue());
            var part  = {value: value.part, type: value.part};

            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var selectedPartLabel   = this._getSelectedChoiceLabel('dateParts', part);
            this.dateWidgetOptions.part = part.type;

            var datePartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];

            // add date parts only if embed template used
            if (this.templateTheme !== "") {
                parts.push(
                    datePartTemplate({
                        name: this.name + '_part',
                        choices: this.dateParts,
                        selectedChoice: value.part,
                        selectedChoiceLabel: selectedPartLabel
                    })
                );
            }

            parts.push(
                datePartTemplate({
                    name: this.name,
                    choices: this.choices,
                    selectedChoice: value.type,
                    selectedChoiceLabel: selectedChoiceLabel
                })
            );

            var displayValue = this._formatDisplayValue(value);
            var $filter = $(
                this.template({
                    inputClass: this.inputClass,
                    value: displayValue,
                    parts: parts
                })
            );

            this._appendFilter($filter);
            this.$(this.criteriaSelector).attr('tabindex', '0');

            this._renderSubViews();
            this.changeFilterType(value.type);

            this._criteriaRenderd = true;
        },

        /**
         * Renders picker views for both start and end values
         *
         * @protected
         */
        _renderSubViews: function () {
            var name, selector, pickerView, options,
                value = this.criteriaValueSelectors.value;
            for (name in value) {
                if (!value.hasOwnProperty(name)) {
                    return;
                }
                selector = value[name];
                options = this._getPickerConfigurationOptions({
                    el: this.$(selector)
                });
                pickerView = new this.picker(options);
                this.subview(name, pickerView);
            }
        },

        /**
         * Prepares configuration options for picker view
         *
         * @param {Object} options
         * @returns {Object}
         * @protected
         */
        _getPickerConfigurationOptions: function (options) {
            _.extend(options, {
                nativeMode: tools.isMobile(),
                dateInputAttrs: {
                    'class': this.inputClass
                },
                datePickerOptions: this.dateWidgetOptions,
                dropdownTemplate: this._getTemplate(this.dropdownTemplateSelector),
                backendFormat: datetimeFormatter.getDateFormat()
            });
            return options;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function () {
            var hint = '',
                option, start, end, type,
                value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            if (value.value) {
                start = value.value.start;
                end   = value.value.end;
                type  = value.type ? value.type.toString() : '';

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
                    case this.typeValues.between.toString():
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
        _formatDisplayValue: function (value) {
            if (value.value && value.value.start) {
                value.value.start = this._toDisplayValue(value.value.start);
            }
            if (value.value && value.value.end) {
                value.value.end = this._toDisplayValue(value.value.end);
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function (value) {
            if (value.value && value.value.start) {
                value.value.start = this._toRawValue(value.value.start);
            }
            if (value.value && value.value.end) {
                value.value.end = this._toRawValue(value.value.end);
            }
            return value;
        },

        /**
         * Converts the date value from Raw to Display
         *
         * @param {string} value
         * @returns {string}
         * @protected
         */
        _toDisplayValue: function (value) {
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else if (datetimeFormatter.isBackendDateValid(value)) {
                value = datetimeFormatter.formatDate(value);
            }
            return value;
        },

        /**
         * Converts the date value from Display to Raw         *
         * @param {string} value
         * @returns {string}
         * @protected
         */
        _toRawValue: function (value) {
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else if (datetimeFormatter.isDateValid(value)) {
                value = datetimeFormatter.convertDateToBackendFormat(value);
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function (value) {
            this._setInputValue(this.criteriaValueSelectors.value.start, value.value.start);
            this._setInputValue(this.criteriaValueSelectors.value.end, value.value.end);
            this._setInputValue(this.criteriaValueSelectors.date_type, value.type);
            if (value.part) {
                this._setInputValue(this.criteriaValueSelectors.date_part, value.part);
            }
            return this;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function () {
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
        _focusCriteria: function () {
            this.$(this.criteriaSelector).focus();
        },

        _getSelectedChoiceLabel: function (property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function (choice) {
                    return (choice.value == value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }

            return selectedChoiceLabel;
        }
    });

    return DateFilter;
});
