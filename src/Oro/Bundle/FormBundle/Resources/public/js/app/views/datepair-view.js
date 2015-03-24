/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'moment',
    'oroui/js/app/views/base/view',
    'oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker',
    'oroui/lib/jquery.datepair-0.4.4/jquery.datepair.min'
], function ($, _, moment, BaseView) {
    'use strict';

    var _ONE_DAY = 86400000;
    var DatepairView;
    DatepairView = BaseView.extend({

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
         * Date Time Separator
         */
        dateTimeSeparator: ' ',

        /**
         * Default options
         */
        options: {
            startClass: 'start',
            endClass: 'end',
            timeClass: 'time',
            dateClass: 'date',
            containerSelector: 'form',
            defaultDateDelta: 0,
            defaultTimeDelta: 3600000
        },

        /**
         * {Object} Container
         */
        $container: null,

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function (options) {
            $.extend(this, _.pick(options, ['nativeMode']));
            DatepairView.__super__.initialize.apply(this, arguments);
            this.$container = $(this.$el.parents(this.options.containerSelector));
            this.initDatepair();
            this.bindContainerHandler();
        },

        initDatepair: function () {
            this.$container.datepair({
                startClass: this.options.startClass,
                endClass: this.options.endClass,
                timeClass: this.options.timeClass,
                dateClass: this.options.dateClass,
                parseTime: this._parseTime,
                updateTime: this._updateTime,
                setMinTime: this._setMinTime,
                parseDate: this._parseDate,
                updateDate: this._updateDate,

                nativeMode: this.nativeMode,
                getNativeDateTimeFormat: this.getNativeDateTimeFormat,
                dateTimeSeparator: this.dateTimeSeparator,
                nativeTimeFormat: this.nativeTimeFormat,
                nativeDateFormat: this.nativeDateFormat
            });
        },

        _parseTime: function (input) {
            if (this.nativeMode) {
                var momentInstance = moment($(input).val(), this.nativeTimeFormat, true);

                return momentInstance.toDate();
            } else {
                return $(input).timepicker('getTime');
            }
        },

        _parseDate: function (input) {
            if (this.nativeMode) {
                var momentInstance = moment($(input).val(), this.nativeDateFormat, true);

                return momentInstance.toDate();
            } else {
                return $(input).datepicker('getDate');
            }
        },

        _setMinTime: function (input, dateObj) {
            if (!this.nativeMode) {
                $(input).timepicker('option', 'minTime', dateObj);
            }
        },

        _updateTime: function (input, dateObj) {
            if (this.nativeMode) {
                var momentInstance = moment(dateObj);
                $(input).val(momentInstance.format(this.nativeTimeFormat));
                $(input).trigger('change');
            } else {
                $(input).timepicker('setTime', dateObj);
            }
        },

        _updateDate: function (input, dateObj) {
            if (this.nativeMode) {
                var momentInstance = moment(dateObj);
                $(input).val(momentInstance.format(this.nativeDateFormat));
                $(input).trigger('change');
            } else {
                // calls 'setDate' method instead of native 'update'
                $(input).datepicker('setDate', dateObj);
                // triggers event to update backend field
                $(input).trigger('change');
            }
        },

        bindContainerHandler: function () {
            var self = this;
            this.$container.on('rangeError', function () {
                // resets 'start' and 'end' fields to default values on range error
                var startDateInput = self.$container.find('.' + self.options.startClass + '.' + self.options.dateClass),
                    endDateInput = self.$container.find('.' + self.options.endClass + '.' + self.options.dateClass),
                    startTimeInput = self.$container.find('.' + self.options.startClass + '.' + self.options.timeClass),
                    endTimeInput = self.$container.find('.' + self.options.endClass + '.' + self.options.timeClass);
                var startDate = self._parseDate($(startDateInput)),
                    startTime = self._parseTime($(startTimeInput));
                var newDate = new Date(startDate.getTime() + self.options.defaultDateDelta * _ONE_DAY);
                var newTime = new Date(startTime.getTime() + self.options.defaultTimeDelta);
                self._updateDate($(endDateInput), newDate);
                self._updateTime($(endTimeInput), newTime);
            });
        },

        /**
         * @returns {string}
         */
        getNativeDateTimeFormat: function () {
            return this.nativeDateFormat + this.dateTimeSeparator + this.nativeTimeFormat;
        }
    });

    return DatepairView;
});
