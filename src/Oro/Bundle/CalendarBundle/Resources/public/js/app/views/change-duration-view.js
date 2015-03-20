/*jslint nomen: true*/
/*global define*/
define([
    'jquery', 'underscore', 'orotranslation/js/translator', 'moment', 'oroui/js/tools',
    'orolocale/js/formatter/datetime', 'orolocale/js/locale-settings', 'oroui/js/app/views/base/view'
],
function ($, _, __, moment, tools, datetimeFormatter, localeSettings, BaseView) {
    'use strict';

    var ChangeDurationView = BaseView.extend({

        /**
         * Form row selector with fronted and backend inputs
         */
        formRowSelector: 'div.controls',

        /**
         * Frontend date input selector
         */
        dateHolderSelector: 'input.hasDatepicker',

        /**
         * Frontend time input selector
         */
        timeHolderSelector: 'input.ui-timepicker-input',

        /**
         * Backend start datetime input selector
         */
        startSelector: null,

        /**
         * Backend end datetime input selector
         */
        endSelector: null,

        /**
         * Original difference between start datetime and end datetime in seconds
         */
        diff: 0,

        /**
         * Format of date/datetime that original input accepts
         */
        backendFormat: datetimeFormatter.backendFormats.datetime,

        /**
         * Use native pickers of proper HTML-inputs
         */
        nativeMode: false,

        /**
         * Format of time that native date input accepts
         */
        nativeTimeFormat: 'HH:mm',

        /**
         * Format of date that native date input accepts
         */
        nativeDateFormat: 'YYYY-MM-DD',

        /**
         * @constructor
         */
        initialize: function (options) {
            this.nativeMode = tools.isMobile();
            this.startSelector = options.startSelector;
            this.endSelector = options.endSelector;
            this.storeDiff();
            this.bindEvents();
        },

        /**
         * Stores original difference between start datetime and end datetime in seconds
         */
        storeDiff: function() {
            this.diff = this.getTimestamp($(this.endSelector).val()) - this.getTimestamp($(this.startSelector).val());
        },

        /**
         * Binds event listeners which keeps original duration and fix end timestamp
         */
        bindEvents: function() {
            var self = this;
            $(this.startSelector).on('change', function() {
                var value = $(self.startSelector).val();
                if (self.isValid(value)) {
                    self.applyEndTimestamp(self.getTimestamp(value) + self.diff);
                }
            });

            $(this.endSelector).on('change', function() {
                var startValue = $(self.startSelector).val();
                var endValue = $(self.endSelector).val();
                if (self.isValid(startValue) && self.isValid(endValue)) {
                    var start = self.getTimestamp(startValue);
                    if (self.getTimestamp(endValue) < start) {
                        self.applyEndTimestamp(start);
                    }
                }
            });
        },

        /**
         * Applies timestamp to frontend and backend inputs
         *
         * @param endTimestamp
         */
        applyEndTimestamp: function(endTimestamp) {
            var momentInstance = moment.utc(endTimestamp, 'X');

            $(this.endSelector).val(momentInstance.format(this.getBackendFormat()));
            $(this.endSelector).trigger('change');

            momentInstance.add(localeSettings.getTimeZoneShift(), 'm');
            var endDateHolder = $(this.endSelector).parents(this.formRowSelector).find(this.dateHolderSelector)[0];
            $(endDateHolder).val(momentInstance.format(this.getDateFormat()));
            $(endDateHolder).trigger('change');

            var endTimeHolder = $(this.endSelector).parents(this.formRowSelector).find(this.timeHolderSelector)[0];
            $(endTimeHolder).val(momentInstance.format(this.getTimeFormat()));
            $(endTimeHolder).trigger('change');
        },

        /**
         * Returns whether the value is valid date string
         *
         * @param value
         * @returns {*|Boolean}
         */
        isValid: function(value) {
            return moment.utc(value, this.getBackendFormat(), true).isValid();
        },

        /**
         * Calculates unix timestamp
         *
         * @param value
         * @returns {*}
         */
        getTimestamp: function(value) {
            return moment.utc(value, this.getBackendFormat(), true).unix();
        },

        /**
         * Defines backend format for datetime field
         *
         * @returns {string}
         */
        getBackendFormat: function() {
            return _.isArray(this.backendFormat) ? this.backendFormat[0] : this.backendFormat;
        },

        /**
         * Defines frontend format for time field
         *
         * @returns {string}
         */
        getTimeFormat: function() {
            return this.nativeMode ? this.nativeTimeFormat : datetimeFormatter.getTimeFormat();
        },

        /**
         * Defines frontend format for date field
         *
         * @returns {string}
         */
        getDateFormat: function() {
            return this.nativeMode ? this.nativeDateFormat : datetimeFormatter.getDateFormat();
        }
    });

    return ChangeDurationView;
});
