define([
    'jquery',
    'underscore',
    'moment',
    'oroui/js/app/views/base/view',
    'oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker',
    'oroui/lib/jquery.datepair-0.4.4/jquery.datepair.min'
], function($, _, moment, BaseView) {
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
         * Default options
         */
        options: {
            startClass: 'start',
            endClass: 'end',
            timeClass: 'timepicker-input',
            dateClass: 'datepicker-input',
            defaultDateDelta: 0,
            defaultTimeDelta: 3600000
        },

        events: {
            rangeError: 'handleRangeError'
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['nativeMode']));
            this.options = _.defaults(_.pick(options, _.keys(this.options)), this.options);
            DatepairView.__super__.initialize.apply(this, arguments);
            this.initDatepair();
        },

        initDatepair: function() {
            this.$el.datepair({
                startClass: this.options.startClass,
                endClass: this.options.endClass,
                timeClass: this.options.timeClass,
                dateClass: this.options.dateClass,
                parseTime: _.bind(this._parseTime, this),
                updateTime: _.bind(this._updateTime, this),
                setMinTime: _.bind(this._setMinTime, this),
                parseDate: _.bind(this._parseDate, this),
                updateDate: _.bind(this._updateDate, this)
            });
        },

        _parseTime: function(input) {
            if (this.nativeMode) {
                var momentInstance = moment($(input).val(), this.nativeTimeFormat, true);

                return momentInstance.toDate();
            } else {
                return $(input).timepicker('getTime');
            }
        },

        _parseDate: function(input) {
            if (this.nativeMode) {
                var momentInstance = moment($(input).val(), this.nativeDateFormat, true);

                return momentInstance.toDate();
            } else {
                return $(input).datepicker('getDate');
            }
        },

        _setMinTime: function(input, dateObj) {
            if (!this.nativeMode) {
                $(input).timepicker('option', 'minTime', dateObj);
            }
        },

        _updateTime: function(input, dateObj) {
            if (this.nativeMode) {
                var momentInstance = moment(dateObj);
                $(input).val(momentInstance.format(this.nativeTimeFormat));
            } else {
                $(input).timepicker('setTime', dateObj);
            }
            // triggers event to update backend field
            $(input).trigger('change');
        },

        _updateDate: function(input, dateObj) {
            if (this.nativeMode) {
                var momentInstance = moment(dateObj);
                $(input).val(momentInstance.format(this.nativeDateFormat));
            } else {
                // calls 'setDate' method instead of native 'update'
                $(input).datepicker('setDate', dateObj);
            }
            // triggers event to update backend field
            $(input).trigger('change');
        },

        handleRangeError: function() {
            // resets 'start' and 'end' fields to default values on range error
            var startDateInput = this.$('.' + this.options.startClass + '.' + this.options.dateClass);
            var endDateInput = this.$('.' + this.options.endClass + '.' + this.options.dateClass);
            var startTimeInput = this.$('.' + this.options.startClass + '.' + this.options.timeClass);
            var endTimeInput = this.$('.' + this.options.endClass + '.' + this.options.timeClass);
            var startDate = this._parseDate($(startDateInput));
            var startTime = this._parseTime($(startTimeInput));
            var newDate = new Date(startDate.getTime() + this.options.defaultDateDelta * _ONE_DAY);
            var newTime = new Date(startTime.getTime() + this.options.defaultTimeDelta);
            this._updateDate($(endDateInput), newDate);
            this._updateTime($(endTimeInput), newTime);
        }
    });

    return DatepairView;
});
