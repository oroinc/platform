/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var DatetimeFilter,
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        VariableDateTimePickerView = require('orofilter/js/app/views/datepicker/variable-datetimepicker-view'),
        DateFilter = require('./date-filter');

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
                fieldsWrapper: '<div></div>',
                backendFormat: [datetimeFormatter.getDateTimeFormat(), this.backendFormat]
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
        }
    });

    return DatetimeFilter;
});
