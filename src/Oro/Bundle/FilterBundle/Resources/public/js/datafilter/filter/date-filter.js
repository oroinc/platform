/*global define*/
define(['jquery', 'underscore', 'oro/translator', './choice-filter', 'oro/locale-settings', 'jquery-ui-datevariables'],
function ($, _, __, ChoiceFilter, localeSettings) {
    'use strict';

    /**
     * Date filter: filter type as option + interval begin and end dates
     *
     * @export  oro/datafilter/date-filter
     * @class   oro.datafilter.DateFilter
     * @extends oro.datafilter.ChoiceFilter
     */
    return ChoiceFilter.extend({
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
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select[name=date]',
            part: 'select[name=date_part]',
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
            className:      'date-filter-widget',
            showButtonPanel: true
        },

        /**
         * Additional date widget options that might be passed to filter
         * http://api.jqueryui.com/datepicker/
         *
         * @property
         */
        externalWidgetOptions: {},

        /**
         * References to date widgets
         *
         * @property
         */
        dateWidgets: {
            start: null,
            end: null
        },

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
         * Date widget selector
         *
         * @property
         */
        dateWidgetSelector: 'div#ui-datepicker-div.ui-datepicker',

        /**
         * Date parts
         *
         * @property
         */
        dateParts: [],

        /**
         * @inheritDoc
         */
        initialize: function () {
            _.extend(this.dateWidgetOptions, this.externalWidgetOptions);

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

            ChoiceFilter.prototype.initialize.apply(this, arguments);
        },

        onChangeFilterType: function (e) {
            var select = this.$el.find(e.currentTarget);
            var selectedValue = select.val() || select.data('value');

            this.changeFilterType(selectedValue);
        },

        changeFilterType: function (type) {
            if (this.dateWidgets.start && this.dateWidgets.start.data('datepicker')) {
                this.dateWidgets.start.data('datepicker').settings.part = type;
            }
            if (this.dateWidgets.end && this.dateWidgets.end.data('datepicker')) {
                this.dateWidgets.end.data('datepicker').settings.part = type;
            }

            type = parseInt(type, 10);
            this.$el.find('.filter-separator').show().end().find('input').show();
            if (this.typeValues.moreThan === type) {
                this.$el.find('.filter-separator').hide();
                this.$el.find(this.criteriaValueSelectors.value.end).val('').hide();
            } else if (this.typeValues.lessThan === type) {
                this.$el.find('.filter-separator').hide();
                this.$el.find(this.criteriaValueSelectors.value.start).val('').hide();
            }
        },

        /**
         * @inheritDoc
         */
        render: function () {
            var value = _.extend({}, this.emptyValue, this.value);
            var part  = {value: value.part, type: value.part};

            var selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            var selectedPartLabel   = this._getSelectedChoiceLabel('dateParts', part);
            this.dateWidgetOptions.part = part.type;

            var datePartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];

            // add date parts only if embed template used
            if (this.templateTheme != "") {
                parts.push(
                    datePartTemplate({
                        name: this.name+'_part',
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

            var $filter = $(
                this.template({
                    inputClass: this.inputClass,
                    value: this._formatDisplayValue(value),
                    parts: parts
                })
            );
            this._wrap($filter);

            this.changeFilterType(value.type);

            $filter.find('select:first').bind('change', _.bind(this.onChangeFilterType, this));

            _.each(this.criteriaValueSelectors.value, function(actualSelector, name) {
                this.dateWidgets[name] = this._initializeDateWidget(actualSelector);
            }, this);

            return this;
        },

        /**
         * Initialize date widget
         *
         * @param {String} widgetSelector
         * @return {*}
         * @protected
         */
        _initializeDateWidget: function(widgetSelector) {
            this.$(widgetSelector).datevariables(this.dateWidgetOptions);
            var widget = this.$(widgetSelector).datevariables('widget');
            widget.addClass(this.dateWidgetOptions.className);
            $(this.dateWidgetSelector).on('click', function(e) {
                e.stopImmediatePropagation();
            });
            return widget;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
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
        _formatDisplayValue: function(value) {
            var fromFormat = this.dateWidgetOptions.altFormat;
            var toFormat = this.dateWidgetOptions.dateFormat;

            if (value.value && value.value.start) {
                value.value.start = this._replaceDateVars(value.value.start, 'display');
            }
            if (value.value && value.value.end) {
                value.value.end = this._replaceDateVars(value.value.end, 'display');
            }

            return this._formatValueDates(value, fromFormat, toFormat);
        },

        /**
         * @inheritDoc
         */
        _formatRawValue: function(value) {
            var fromFormat = this.dateWidgetOptions.dateFormat;
            var toFormat = this.dateWidgetOptions.altFormat;

            if (value.value && value.value.start) {
                value.value.start = this._replaceDateVars(value.value.start, 'raw');
            }
            if (value.value && value.value.end) {
                value.value.end = this._replaceDateVars(value.value.end, 'raw');
            }

            return this._formatValueDates(value, fromFormat, toFormat);
        },

        /**
         * Format datetes in a valut to another format
         *
         * @param {Object} value
         * @param {String} fromFormat
         * @param {String} toFormat
         * @return {Object}
         * @protected
         */
        _formatValueDates: function(value, fromFormat, toFormat) {
            if (value.value && value.value.start) {
                value.value.start = this._formatDate(value.value.start, fromFormat, toFormat);
            }
            if (value.value && value.value.end) {
                value.value.end = this._formatDate(value.value.end, fromFormat, toFormat);
            }
            return value;
        },

        _replaceDateVars: function(value, mode) {
            // replace date variables with constant values
            var dateVars = this.dateWidgetOptions.dateVars;

            if (mode == 'raw') {
                for (var part in dateVars) {
                    for (var varCode in dateVars[part]) {
                        value = value.replace(new RegExp(dateVars[part][varCode], 'g'), '{{' + varCode+'}}');
                    }
                }
            } else {
                for (var part in dateVars) {
                    for (var varCode in dateVars[part]) {
                        value = value.replace(new RegExp('\{+' + varCode + '\}+', 'gi'), dateVars[part][varCode]);
                    }
                }
            }

            return value;
        },

        /**
         * Formats date string to another format
         *
         * @param {String} value
         * @param {String} fromFormat
         * @param {String} toFormat
         * @return {String}
         * @protected
         */
        _formatDate: function(value, fromFormat, toFormat) {
            var fromValue = $.datepicker.parseDate(fromFormat, value);
            if (!fromValue) {
                fromValue = $.datepicker.parseDate(toFormat, value);
                if (!fromValue) {
                    return value;
                }
            }
            return $.datepicker.formatDate(toFormat, fromValue);
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.value.start, value.value.start);
            this._setInputValue(this.criteriaValueSelectors.value.end, value.value.end);
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
            if (value.part) {
                this._setInputValue(this.criteriaValueSelectors.part, value.part);
            }
            return this;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            return {
                type: this._getInputValue(this.criteriaValueSelectors.type),
                part: this._getInputValue(this.criteriaValueSelectors.part),
                value: {
                    start: this._getInputValue(this.criteriaValueSelectors.value.start),
                    end:   this._getInputValue(this.criteriaValueSelectors.value.end)
                }
            };
        },

        /**
         * @inheritDoc
         */
        _focusCriteria: function() {},

        /**
         * @inheritDoc
         */
        _hideCriteria: function() {
            ChoiceFilter.prototype._hideCriteria.apply(this, arguments);
        },

        _getSelectedChoiceLabel: function(property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function(choice) {
                    return (choice.value == value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }

            return selectedChoiceLabel;
        }
    });
});
