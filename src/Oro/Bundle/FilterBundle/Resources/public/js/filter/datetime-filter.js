define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const moment = require('moment');
    const __ = require('orotranslation/js/translator');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');
    const FilterDateTimePickerView = require('orofilter/js/app/views/datepicker/filter-datetimepicker-view').default;
    const VariableDateTimePickerView = require('orofilter/js/app/views/datepicker/variable-datetimepicker-view');
    const DateFilter = require('oro/filter/date-filter');
    const tools = require('oroui/js/tools');
    const KEYBOARD_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    let config = require('module-config').default(module.id);

    config = _.extend({
        inputClass: 'datetime-visual-element',
        timeInputAttrs: {
            'class': 'timepicker-input',
            'placeholder': 'oro.form.choose_time'
        }
    }, config);

    config.timeInputAttrs.placeholder = __(config.timeInputAttrs.placeholder);

    /**
     * Datetime filter: filter type as option + interval begin and end dates
     */
    const DatetimeFilter = DateFilter.extend({
        /**
         * CSS class for visual datetime input elements
         *
         * @property
         */
        inputClass: config.inputClass,

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select', // to handle both type and part changes
            date_type: 'select[name][name!=datetime_part]',
            date_part: 'select[name=datetime_part]',
            value: {
                start: 'input[name="start"]',
                end: 'input[name="end"]'
            }
        },

        /**
         * Datetime filter uses custom format to backend datetime
         */
        backendFormat: 'YYYY-MM-DD HH:mm',

        events: {
            // timepicker triggers this event on mousedown and hides picker's dropdown
            'hideTimepicker input': '_onHideTimepicker',
            'showTimepicker input': '_onShowTimepicker'
        },

        /**
         * @inheritdoc
         */
        constructor: function DatetimeFilter(options) {
            DatetimeFilter.__super__.constructor.call(this, options);
        },

        _getPickerConstructor: function() {
            return tools.isMobile() || !this.dateWidgetOptions.showDatevariables
                ? FilterDateTimePickerView : VariableDateTimePickerView;
        },

        _renderCriteria: function() {
            DatetimeFilter.__super__._renderCriteria.call(this);

            const value = this.getValue();
            if (value) {
                this._updateTimeVisibility(value.part);
            }
        },

        /**
         * Handle click outside of criteria popup to hide it
         *
         * @param {Event} e
         * @protected
         */
        _onClickOutsideCriteria: function(e) {
            if (this._justPickedTime) {
                this._justPickedTime = false;
            } else {
                DatetimeFilter.__super__._onClickOutsideCriteria.call(this, e);
            }
        },

        /**
         * Turns on flag that time has been just picked,
         * to prevent closing the criteria dropdown
         *
         * @protected
         */
        _preventClickOutsideCriteria() {
            this._justPickedTime = true;
        },

        _onHideTimepicker() {
            this._preventClickOutsideCriteria();
            this._preventCloseCriteria = true;
        },

        _onShowTimepicker() {
            this._preventCloseCriteria = false;
        },

        onKeyDownTimepickerInput(e) {
            /*
                1. Prevent first press by ESC;
                2. ESC and SPACE close timepicker so need to set opposite value;
             */
            if (
                e.keyCode === KEYBOARD_CODES.ESCAPE ||
                e.keyCode === KEYBOARD_CODES.ENTER
            ) {
                this._preventCloseCriteria = !this._preventCloseCriteria;
            }

            const startView = this.subviewsByName['start'];
            if (
                e.keyCode === KEYBOARD_CODES.ESCAPE &&
                !startView.nativeMode &&
                this._preventCloseCriteria
            ) {
                e.stopPropagation();
            }
        },

        /**
         * @inheritdoc
         */
        _getPickerConfigurationOptions: function(optionsToMerge, parameters) {
            DatetimeFilter.__super__._getPickerConfigurationOptions.call(this, optionsToMerge, parameters);

            const {startTimeFieldAriaLabel, endTimeFieldAriaLabel} = this.getTemplateDataProps();
            const labelsMap = {
                start: startTimeFieldAriaLabel,
                end: endTimeFieldAriaLabel
            };
            let ariaLabel = null;

            if (labelsMap[parameters.criteriaValueName]) {
                ariaLabel = labelsMap[parameters.criteriaValueName];
            }

            _.extend(optionsToMerge, {
                backendFormat: [datetimeFormatter.getDateTimeFormat(), this.backendFormat],
                timezone: 'UTC',
                timeInputAttrs: {
                    'aria-label': ariaLabel,
                    ...config.timeInputAttrs
                },
                timePickerOptions: {...this.timePickerOptions || {}}
            });

            return optionsToMerge;
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
            let momentInstance;
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else if (part === 'value' && this.dateValueHelper.isValid(value)) {
                value = this.dateValueHelper.formatDisplayValue(value);
            } else if (datetimeFormatter.isValueValid(value, this.backendFormat)) {
                momentInstance = moment(value, this.backendFormat, true);
                value = momentInstance.format(datetimeFormatter.getDateTimeFormat());
            }
            return value;
        },

        /**
         * Converts the date value from Display to Raw
         *
         * @param {string} value
         * @param {string} part
         * @returns {string}
         * @protected
         */
        _toRawValue: function(value, part) {
            let momentInstance;
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else if (part === 'value' && this.dateValueHelper.isValid(value)) {
                value = this.dateValueHelper.formatRawValue(value);
            } else if (datetimeFormatter.isDateTimeValid(value)) {
                momentInstance = moment(value, datetimeFormatter.getDateTimeFormat(), true);
                value = momentInstance.format(this.backendFormat);
            }
            return value;
        },

        /**
         * @inheritdoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (this.isUpdatable(newValue, oldValue)) {
                this._updateTimeVisibility(newValue.part);
            }
            DatetimeFilter.__super__._triggerUpdate.call(this, newValue, oldValue);
        },

        _renderSubViews: function() {
            DatetimeFilter.__super__._renderSubViews.call(this);
            const value = this._readDOMValue();
            this._updateDateTimePickerSubView('start', value);
            this._updateDateTimePickerSubView('end', value);
            this.$el.find('.timepicker-input')
                .bindFirst(`keydown${this.eventNamespace()}`, this.onKeyDownTimepickerInput.bind(this));
        },

        _updateDateTimePickerSubView: function(subViewName, viewValue) {
            const subView = this.subview(subViewName);
            if (!subView || !subView.updateFront) {
                return;
            }

            subView.updateFront();
        },

        _updateTimeVisibility: function(part) {
            if (part === 'value') {
                this.$('.timepicker-input').removeClass('hide');
            } else {
                this.$('.timepicker-input').addClass('hide');
            }
        },

        getTemplateDataProps() {
            const data = DatetimeFilter.__super__.getTemplateDataProps.call(this);

            return {
                ...data,
                startTimeFieldAriaLabel: __('oro.filter.datetime.start_field.aria_label', {
                    label: this.label
                }),
                endTimeFieldAriaLabel: __('oro.filter.datetime.end_field.aria_label', {
                    label: this.label
                })
            };
        },

        /**
         * @inheritdoc
         */
        dispose() {
            if (this.disposed) {
                return;
            }

            this.$el.find('.timepicker-input').off(this.eventNamespace());
            DatetimeFilter.__super__.dispose.call(this);
        }
    });

    return DatetimeFilter;
});
