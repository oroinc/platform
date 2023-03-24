define(function(require, exports, module) {
    'use strict';

    const template = require('tpl-loader!orofilter/templates/filter/date-filter.html');
    const fieldTemplate = require('tpl-loader!orofilter/templates/filter/select-field.html');
    const dropdownTemplate = require('tpl-loader!orofilter/templates/filter/date-filter-dropdown.html');
    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const __ = require('orotranslation/js/translator');
    const ChoiceFilter = require('oro/filter/choice-filter');
    const FilterDatePickerView = require('orofilter/js/app/views/datepicker/filter-datepicker-view').default;
    const VariableDatePickerView = require('orofilter/js/app/views/datepicker/variable-datepicker-view');
    const DateVariableHelper = require('orofilter/js/date-variable-helper');
    const DateValueHelper = require('orofilter/js/date-value-helper');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');
    const localeSettings = require('orolocale/js/locale-settings');
    const layout = require('oroui/js/layout');
    let config = require('module-config').default(module.id);

    config = _.extend({
        inputClass: 'date-visual-element'
    }, config);

    require('orofilter/js/datevariables-widget');

    /**
     * Date filter: filter type as option + interval begin and end dates
     */
    const DateFilter = ChoiceFilter.extend({
        /**
         * Template selector for filter criteria
         *
         * @property
         */
        template: template,
        templateSelector: '#date-filter-template',

        /**
         * Template selector for date field parts
         *
         * @property
         */
        fieldTemplate: fieldTemplate,
        fieldTemplateSelector: '#select-field-template',

        /**
         * Template selector for dropdown container
         *
         * @property
         */
        dropdownTemplate: dropdownTemplate,
        dropdownTemplateSelector: '#date-filter-dropdown-template',

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select', // to handle both type and part changes
            date_type: 'select[name][name!=date_part]',
            date_part: 'select[name=date_part]',
            value: {
                start: 'input[name="start"]',
                end: 'input[name="end"]'
            }
        },

        selectors: {
            startContainer: '.filter-start-date',
            separator: '.filter-separator',
            endContainer: '.filter-end-date'
        },

        /**
         * @inheritdoc
         */

        className: 'date-filter-container',

        /**
         * CSS class for custom date range
         *
         * @property
         */
        customClass: 'date-filter-custom',

        /**
         * CSS class for visual date input elements
         *
         * @property
         */
        inputClass: config.inputClass,

        /**
         * Date widget options
         *
         * @property
         */
        dateWidgetOptions: {
            changeMonth: true,
            changeYear: true,
            yearRange: '-80:+1',
            dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'),
            altFormat: 'yy-mm-dd',
            className: 'date-filter-widget',
            showButtonPanel: true
        },

        /**
         * View constructor for picker element
         *
         * @property
         */
        pickerConstructor: null,

        /**
         * View constructor for picker element
         *
         * @property
         */
        variablePickerConstructor: null,

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
            between: 1,
            notBetween: 2,
            moreThan: 3,
            lessThan: 4,
            equal: 5,
            notEqual: 6
        },

        /**
         * @property
         */
        typeDefinedValues: {
            none: 0,
            today: 7,
            this_week: 8,
            this_month: 9,
            this_quarter: 10,
            this_year: 11,
            all_time: 12
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
            year: 'oro.filter.date.part.year.tooltip'
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
         * Flag to allow filter type swap start and end dates if end date is behind start date
         *
         * @property
         */
        autoUpdateRangeFilterType: true,

        /**
         * Flag to allow filter type change if start or end date is missing
         *
         * @property
         */
        autoUpdateBetweenWhenOneDate: true,

        /**
         * @inheritdoc
         */
        constructor: function DateFilter(options) {
            DateFilter.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         * @param {Array.<string>=} options.dayFormats List of acceptable day formats
         * @inheritdoc
         */
        initialize: function(options) {
            this.dayFormats = options && options.dayFormats || [datetimeFormatter.getDayFormat()];
            // make own copy of options
            this.dateWidgetOptions = $.extend(true, {}, this.dateWidgetOptions, this.externalWidgetOptions);
            this.dateVariableHelper = new DateVariableHelper(this.dateWidgetOptions.dateVars);
            this.dateValueHelper = new DateValueHelper(this.dayFormats.slice());

            // parts rendered only if theme exist
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
                    const value = i.toString();

                    return {
                        value: value,
                        label: option,
                        tooltip: this._getPartTooltip(value)
                    };
                }, this);
            }

            if (_.isUndefined(this.emptyPart)) {
                const firstPart = _.first(this.dateParts).value;
                this.emptyPart = {
                    type: (_.isEmpty(this.dateParts) ? '' : firstPart),
                    value: firstPart
                };
            }

            DateFilter.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.dateParts;
            delete this.emptyPart;
            delete this.emptyValue;
            // Remove event handlers from a part of the template
            this.$(`[data-cid="filter-${this.cid}"]`).off(this.eventNamespace());
            DateFilter.__super__.dispose.call(this);
        },

        _getPickerConstructor: function() {
            return this.isSimplePickerView() ? FilterDatePickerView : VariableDatePickerView;
        },

        /**
         * Picker will be in dropdown
         * @returns {*|boolean}
         */
        isSimplePickerView() {
            return tools.isMobile() || !this.dateWidgetOptions.showDatevariables;
        },

        onChangeFilterType: function(e) {
            const select = this.$el.find(e.currentTarget);
            const value = select.val();
            this.changeFilterType(value);
        },

        onFilterRemove(e) {
            this.removeSubview('start');
            this.removeSubview('end');
            this._criteriaRenderd = false;
        },

        /**
         * @inheritdoc
         */
        _applyValueAndHideCriteria: function() {
            this._beforeApply();
            DateFilter.__super__._applyValueAndHideCriteria.call(this);
        },

        changeFilterType: function(value) {
            const startSeparatorEndSelector =
                [this.selectors.startContainer, this.selectors.separator, this.selectors.endContainer].join(',');
            const startSeparatorSelector = [this.selectors.startContainer, this.selectors.separator].join(',');
            const separatorEndSelector = [this.selectors.separator, this.selectors.endContainer].join(',');
            const type = parseInt(value, 10);
            if (!isNaN(type)) {
                // it's type
                this.$(startSeparatorEndSelector).css('display', '');
                this.$el.addClass(this.customClass);
                const typeDefinedValues = [
                    this.typeDefinedValues.none,
                    this.typeDefinedValues.today,
                    this.typeDefinedValues.this_week,
                    this.typeDefinedValues.this_month,
                    this.typeDefinedValues.this_quarter,
                    this.typeDefinedValues.this_year,
                    this.typeDefinedValues.all_time
                ];
                if (typeDefinedValues.indexOf(type) > -1) {
                    this.$(startSeparatorEndSelector).hide();
                    this.$el.removeClass(this.customClass);
                    this.subview('start').setValue('');
                    this.subview('end').setValue('');
                } else if (this.typeValues.moreThan === type) {
                    this.$(separatorEndSelector).hide();
                    this.subview('end').setValue('');
                } else if (this.typeValues.lessThan === type) {
                    this.$(startSeparatorSelector).hide();
                    this.subview('start').setValue('');
                } else if (this.typeValues.equal === type) {
                    this.$(separatorEndSelector).hide();
                    this.subview('end').setValue('');
                } else if (this.typeValues.notEqual === type) {
                    this.$(startSeparatorSelector).hide();
                    this.subview('start').setValue('');
                }

                this.$(this.criteriaValueSelectors.date_type)
                    .closest('.dropdown')
                    .find('[data-toggle="dropdown"]')
                    .html(this.$(this.criteriaValueSelectors.date_type + ' :selected').text());
            } else {
                // it's part
                this.subview('start').setPart(value);
                this.subview('start').setValue('');
                this.subview('end').setPart(value);
                this.subview('end').setValue('');

                this.$(this.criteriaValueSelectors.date_part)
                    .closest('.dropdown')
                    .find('[data-toggle="dropdown"]')
                    .attr('title', this._getPartTooltip(value));
            }
        },

        /**
         * @inheritdoc
         */
        _renderCriteria: function() {
            const value = _.extend({}, this.emptyValue, this.getValue());
            const part = {value: value.part, type: value.part};

            this.dateWidgetOptions.part = part.type;

            this._updateRangeFilter(value, false);

            const displayValue = this._formatDisplayValue(value);
            const $filter = $(
                this.template({
                    inputClass: this.inputClass,
                    value: displayValue,
                    parts: this._getParts(),
                    popoverContent: __('oro.filter.date.info'),
                    renderMode: this.renderMode,
                    ...this.getTemplateDataProps()
                })
            );

            this._appendFilter($filter);

            $filter.attr('data-cid', `filter-${this.cid}`);
            $filter.one(`remove${this.eventNamespace()}`, this.onFilterRemove.bind(this));

            this.$(this.criteriaSelector).attr('tabindex', '0');

            this._renderSubViews();
            this.changeFilterType(value.type);
            layout.initPopover(this.$el);

            if (value) {
                this._updateTooltipVisibility(value.part);
            }
            this.on('update', () => {
                const value = this.getValue();
                if (value) {
                    this._updateTooltipVisibility(value.part);
                }
            });

            this.$el.inputWidget('seekAndCreate');

            this._criteriaRenderd = true;
            this._isRenderingInProgress = false;
        },

        _getParts: function() {
            const value = _.extend({}, this.emptyValue, this.getValue());
            const part = {value: value.part, type: value.part};

            const selectedChoiceLabel = this._getSelectedChoiceLabel('choices', value);
            const selectedPartLabel = this._getSelectedChoiceLabel('dateParts', part);
            const datePartTemplate = this._getTemplate('fieldTemplate');
            const parts = [];

            // add date parts only if embed template used
            if (this.templateTheme !== '') {
                parts.push(
                    datePartTemplate({
                        name: this.name + '_part',
                        choices: this.dateParts,
                        selectedChoice: value.part,
                        selectedChoiceLabel: selectedPartLabel,
                        selectedChoiceTooltip: this._getPartTooltip(value.part),
                        renderMode: this.renderMode,
                        ...this.getTemplateDataProps()
                    })
                );
            }

            parts.push(
                datePartTemplate({
                    name: this.name,
                    choices: this.choices,
                    selectedChoice: value.type,
                    selectedChoiceLabel: selectedChoiceLabel,
                    popoverContent: __('oro.filter.date.info'),
                    renderMode: this.renderMode,
                    ...this.getTemplateDataProps()
                })
            );

            return parts;
        },

        /**
         * Renders picker views for both start and end values
         *
         * @protected
         */
        _renderSubViews: function() {
            let name;
            let selector;
            let pickerView;
            let options;
            const value = this.criteriaValueSelectors.value;
            const Picker = this._getPickerConstructor();
            for (name in value) {
                if (!value.hasOwnProperty(name)) {
                    continue;
                }
                selector = value[name];
                options = this._getPickerConfigurationOptions({
                    el: this.$(selector)
                }, {criteriaValueName: name});
                pickerView = new Picker(options);
                this.subview(name, pickerView);
            }
        },

        _updateValueField: function() {
            // nothing to do
        },

        /**
         * Prepares configuration options for picker view
         *
         * @param {Object} optionsToMerge
         * @param {Object} [parameters]
         * @returns {Object}
         * @protected
         */
        _getPickerConfigurationOptions: function(optionsToMerge, parameters = {}) {
            const {startDateFieldAriaLabel, endDateFieldAriaLabel} = this.getTemplateDataProps();
            const labelsMap = {
                start: startDateFieldAriaLabel,
                end: endDateFieldAriaLabel
            };
            let ariaLabel = null;

            if (labelsMap[parameters.criteriaValueName]) {
                ariaLabel = labelsMap[parameters.criteriaValueName];
            }

            _.extend(optionsToMerge, {
                nativeMode: tools.isMobile(),
                dateInputAttrs: {
                    'class': 'datepicker-input ' + this.inputClass,
                    'placeholder': __('oro.form.choose_date'),
                    'aria-label': ariaLabel
                },
                datePickerOptions: this.dateWidgetOptions,
                dropdownTemplate: this._getTemplate('dropdownTemplate'),
                backendFormat: datetimeFormatter.getDateFormat(),
                dayFormats: this.dayFormats.slice()
            });

            return optionsToMerge;
        },

        /**
         * @inheritdoc
         */
        _getCriteriaHint: function(...args) {
            let hint = '';
            let option;
            const value = (args.length > 0) ? this._getDisplayValue(args[0]) : this._getDisplayValue();
            if (value.value) {
                const start = value.value.start;
                const end = value.value.end;
                const type = value.type ? value.type.toString() : '';

                switch (type) {
                    case this.typeValues.moreThan.toString():
                        hint += [__('oro.filter.date.later_than'), start].join(' ');
                        break;
                    case this.typeValues.lessThan.toString():
                        hint += [__('oro.filter.date.earlier_than'), end].join(' ');
                        break;
                    case this.typeValues.equal.toString():
                        option = this._getChoiceOption(this.typeValues.equal);
                        hint += [option.label, start].join(' ');
                        break;
                    case this.typeValues.notEqual.toString():
                        option = this._getChoiceOption(this.typeValues.notEqual);
                        hint += [option.label, end].join(' ');
                        break;
                    case this.typeValues.notBetween.toString():
                        if (start && end) {
                            option = this._getChoiceOption(this.typeValues.notBetween);
                            hint += [option.label, start, __('oro.filter.date.and'), end].join(' ');
                        } else if (start) {
                            hint += [__('oro.filter.date.before'), start].join(' ');
                        } else if (end) {
                            hint += [__('oro.filter.date.after'), end].join(' ');
                        }
                        break;
                    default:
                        if (start && end) {
                            option = this._getChoiceOption(this.typeValues.between);
                            hint += [option.label, start, __('oro.filter.date.and'), end].join(' ');
                        } else if (start) {
                            hint += [__('oro.filter.date.from'), start].join(' ');
                        } else if (end) {
                            hint += [__('oro.filter.date.to'), end].join(' ');
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
         * @inheritdoc
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
         * @inheritdoc
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
            const oldValue = tools.deepClone(value);
            const type = parseInt(value.type);
            if (value.value &&
                (type === this.typeValues.between || type === this.typeValues.notBetween)) {
                if (value.value.start && value.value.end) {
                    // if both dates are filled
                    if (!this.dateVariableHelper.isDateVariable(value.value.end) &&
                        !this.dateVariableHelper.isDateVariable(value.value.start)) {
                        // swap end/start date if no variables are used and end date is behind start date
                        const end = datetimeFormatter.getMomentForFrontendDateTime(value.value.end);
                        const start = datetimeFormatter.getMomentForFrontendDateTime(value.value.start);
                        if (end < start) {
                            const endValue = value.value.end;
                            value.value.end = value.value.start;
                            value.value.start = endValue;
                        }
                    }
                } else if (this.autoUpdateBetweenWhenOneDate === true) {
                    if (value.value.start || value.value.end) {
                        // if only one date is filled replace filter type to less than or more than
                        if (type === this.typeValues.between) {
                            value.type = value.value.end ? this.typeValues.lessThan : this.typeValues.moreThan;
                        } else if (type === this.typeValues.notBetween) {
                            if (!value.value.end) {
                                // less than type expects end date
                                value.type = this.typeValues.lessThan;
                                value.value.end = value.value.start;
                                value.value.start = '';
                            } else {
                                // more than type expects start date
                                value.type = this.typeValues.moreThan;
                                value.value.start = value.value.end;
                                value.value.end = '';
                            }
                        }
                    }
                }
                if (!tools.isEqualsLoosely(value, oldValue)) {
                    // apply new values and filter type
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
         * @inheritdoc
         */
        isUpdatable: function(newValue, oldValue) {
            return !tools.isEqualsLoosely(newValue, oldValue);
        },

        /**
         * @inheritdoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (this.isUpdatable(newValue, oldValue)) {
                const start = this.subview('start');
                const end = this.subview('end');
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
         * @inheritdoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.value.start, value.value.start);
            this._setInputValue(this.criteriaValueSelectors.value.end, value.value.end);
            const $typeInput = this.$(this.criteriaValueSelectors.date_type);
            if ($typeInput.val() !== value.type) {
                $typeInput.val(value.type).trigger('change');
            }
            if (value.part) {
                this._setInputValue(this.criteriaValueSelectors.date_part, value.part);
            }
            return this;
        },

        /**
         * @inheritdoc
         */
        _readDOMValue: function() {
            if (
                this.subview('start') &&
                this.subview('end') &&
                typeof this._getPickerConstructor().prototype.checkConsistency === 'function'
            ) {
                this.subview('start').checkConsistency(document.activeElement);
                this.subview('end').checkConsistency(document.activeElement);
            }

            return {
                type: this._getInputValue(this.criteriaValueSelectors.date_type),
                // empty default parts value if parts not exist
                part: this.hasPartsElement ? this._getInputValue(this.criteriaValueSelectors.date_part) : 'value',
                value: {
                    start: this._getInputValue(this.criteriaValueSelectors.value.start),
                    end: this._getInputValue(this.criteriaValueSelectors.value.end)
                }
            };
        },

        _getSelectedChoiceLabel: function(property, value) {
            let selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                const foundChoice = _.find(this[property], function(choice) {
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
        },

        /**
         * @inheritdoc
         */
        _isDOMValueChanged: function() {
            const thisDOMValue = this._readDOMValue();
            return (
                !_.isUndefined(thisDOMValue.value) &&
                !_.isUndefined(thisDOMValue.type) &&
                !_.isEqual(this.value, thisDOMValue)
            );
        },

        /**
         * @return {jQuery}
         */
        getCriteriaValueFieldToFocus() {
            const startView = this.subviewsByName['start'];

            if (!startView.nativeMode) {
                return this.$(`${this.criteriaSelector} .datepicker-input`).filter(':visible').first();
            } else {
                return this.$(`${this.criteriaSelector} ${this.criteriaValueSelectors.value.start}`);
            }
        },

        getTemplateDataProps() {
            const data = DateFilter.__super__.getTemplateDataProps.call(this);

            return {
                ...data,
                startDateFieldAriaLabel: __('oro.filter.date.start_field.aria_label', {
                    label: this.label
                }),
                endDateFieldAriaLabel: __('oro.filter.date.end_field.aria_label', {
                    label: this.label
                })
            };
        }
    });

    return DateFilter;
});
