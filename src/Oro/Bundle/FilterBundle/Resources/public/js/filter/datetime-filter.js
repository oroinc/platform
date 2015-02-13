/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DatetimeFilter,
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        VariableDateTimePickerView = require('orofilter/js/app/views/datepicker/variable-datetimepicker-view'),
        DateFilter = require('./date-filter'),
        tools = require('oroui/js/tools');

    /**
     * Datetime filter: filter type as option + interval begin and end dates
     *
     * @export  oro/filter/datetime-filter
     * @class   oro.filter.DatetimeFilter
     * @extends oro.filter.DateFilter
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
        picker: VariableDateTimePickerView,

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'select',// to handle both type and part changes
            date_type: 'select[name!=datetime_part]',
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

        /**
         * Handle click outside of criteria popup to hide it
         *
         * @param {Event} e
         * @protected
         */
        _onClickOutsideCriteria: function (e) {
            if (this._justPickedTime) {
                this._justPickedTime = false
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
        _preventClickOutsideCriteria: function () {
            this._justPickedTime = true;
        },

        /**
         * @inheritDoc
         */
        _getPickerConfigurationOptions: function (options) {
            DatetimeFilter.__super__._getPickerConfigurationOptions.call(this, options);
            _.extend(options, {
                backendFormat: [datetimeFormatter.getDateTimeFormat(), this.backendFormat],
                timezoneShift: 0
            });
            return options;
        },

        /**
         * Converts the date value from Raw to Display
         *
         * @param {string} value
         * @returns {string}
         * @protected
         */
        _toDisplayValue: function (value) {
            var momentInstance;
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
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
         * @returns {string}
         * @protected
         */
        _toRawValue: function (value) {
            var momentInstance;
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else if (datetimeFormatter.isDateTimeValid(value)) {
                momentInstance = moment(value, datetimeFormatter.getDateTimeFormat(), true);
                value = momentInstance.format(this.backendFormat);
            }
            return value;
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function () {
            this.subview('start').checkConsistency();
            this.subview('end').checkConsistency();
            return DatetimeFilter.__super__._readDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _triggerUpdate: function (newValue, oldValue) {
            if (!tools.isEqualsLoosely(newValue, oldValue)) {
                var start = this.subview('start'),
                    end = this.subview('end');
                if (start && start.updateFront) {
                    start.updateFront();
                }
                if (end && end.updateFront) {
                    end.updateFront();
                }
                this.trigger('update');
            }
        }
    });

    return DatetimeFilter;
});
