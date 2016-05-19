define(function(require) {
    'use strict';

    var DatetimeFilter;
    var _ = require('underscore');
    var moment = require('moment');
    var __ = require('orotranslation/js/translator');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var VariableDateTimePickerView = require('orofilter/js/app/views/datepicker/variable-datetimepicker-view');
    var DateFilter = require('./date-filter');
    var tools = require('oroui/js/tools');

    /**
     * Datetime filter: filter type as option + interval begin and end dates
     */
    DatetimeFilter = DateFilter.extend({
        /**
         * CSS class for visual datetime input elements
         *
         * @property
         */
        inputClass: 'datetime-visual-element',

        /**
         * View constructor for picker element
         *
         * @property
         */
        picker: tools.isMobile() ? DateTimePickerView : VariableDateTimePickerView,

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select',// to handle both type and part changes
            date_type: 'select[name][name!=datetime_part]',
            date_part: 'select[name=datetime_part]',
            value: {
                start: 'input[name="start"]',
                end:   'input[name="end"]'
            }
        },

        /**
         * Datetime filter uses custom format to backend datetime
         */
        backendFormat: 'YYYY-MM-DD HH:mm',

        events: {
            // timepicker triggers this event on mousedown and hides picker's dropdown
            'hideTimepicker input': '_preventClickOutsideCriteria'
        },

        _renderCriteria: function() {
            DatetimeFilter.__super__._renderCriteria.apply(this, arguments);

            var value = this.getValue();
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
                DatetimeFilter.__super__._onClickOutsideCriteria.apply(this, arguments);
            }
        },

        /**
         * Turns on flag that time has been just picked,
         * to prevent closing the criteria dropdown
         *
         * @protected
         */
        _preventClickOutsideCriteria: function() {
            this._justPickedTime = true;
        },

        /**
         * @inheritDoc
         */
        _getPickerConfigurationOptions: function(options) {
            DatetimeFilter.__super__._getPickerConfigurationOptions.call(this, options);
            _.extend(options, {
                backendFormat: [datetimeFormatter.getDateTimeFormat(), this.backendFormat],
                timezone: 'UTC',
                timeInputAttrs: {
                    'class': 'timepicker-input',
                    'placeholder': __('oro.form.choose_time')
                }
            });
            return options;
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
            var momentInstance;
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
            var momentInstance;
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
         * @inheritDoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (!tools.isEqualsLoosely(newValue, oldValue)) {
                this._updateTimeVisibility(newValue.part);
            }
            DatetimeFilter.__super__._triggerUpdate.apply(this, arguments);
        },

        _renderSubViews: function() {
            DatetimeFilter.__super__._renderSubViews.apply(this, arguments);
            var value = this._readDOMValue();
            this._updateDateTimePickerSubView('start', value);
            this._updateDateTimePickerSubView('end', value);
        },

        _updateDateTimePickerSubView: function(subViewName, viewValue) {
            var subView = this.subview(subViewName);
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
        }
    });

    return DatetimeFilter;
});
