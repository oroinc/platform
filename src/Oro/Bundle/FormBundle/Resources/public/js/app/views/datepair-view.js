define(function(require) {
    'use strict';

    const _ONE_DAY = 86400000;
    const $ = require('jquery');
    const _ = require('underscore');
    const moment = require('moment');
    const BaseView = require('oroui/js/app/views/base/view');
    const Datepair = require('datepair');

    require('jquery.timepicker');

    const DatepairView = BaseView.extend({

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
         * @inheritdoc
         */
        constructor: function DatepairView(options) {
            DatepairView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['nativeMode']));
            this.options = _.defaults(_.pick(options, _.keys(this.options)), this.options);
            DatepairView.__super__.initialize.call(this, options);
            this.initDatepair();
        },

        initDatepair: function() {
            const options = _.extend(_.pick(this.options, 'startClass', 'endClass', 'timeClass', 'dateClass'), {
                parseTime: this._parseTime.bind(this),
                updateTime: this._updateTime.bind(this),
                setMinTime: this._setMinTime.bind(this),
                parseDate: this._parseDate.bind(this),
                updateDate: this._updateDate.bind(this)
            });

            this.datepair = new Datepair(this.el, options);
        },

        _parseTime: function(input) {
            if (this.nativeMode) {
                const momentInstance = moment($(input).val(), this.nativeTimeFormat, true);

                return momentInstance.toDate();
            } else {
                return $(input).timepicker('getTime');
            }
        },

        _parseDate: function(input) {
            if (this.nativeMode) {
                const momentInstance = moment($(input).val(), this.nativeDateFormat, true);

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
                const momentInstance = moment(dateObj);
                $(input).val(momentInstance.format(this.nativeTimeFormat));
            } else {
                $(input).timepicker('setTime', dateObj);
            }
            // triggers event to update backend field
            $(input).trigger('change');
        },

        _updateDate: function(input, dateObj) {
            if (this.nativeMode) {
                const momentInstance = moment(dateObj);
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
            const startDateInput = this.$('.' + this.options.startClass + '.' + this.options.dateClass);
            const endDateInput = this.$('.' + this.options.endClass + '.' + this.options.dateClass);
            const startTimeInput = this.$('.' + this.options.startClass + '.' + this.options.timeClass);
            const endTimeInput = this.$('.' + this.options.endClass + '.' + this.options.timeClass);
            const startDate = this._parseDate($(startDateInput));
            const startTime = this._parseTime($(startTimeInput));
            const newDate = new Date(startDate.getTime() + this.options.defaultDateDelta * _ONE_DAY);
            const newTime = new Date(startTime.getTime() + this.options.defaultTimeDelta);
            this._updateDate($(endDateInput), newDate);
            this._updateTime($(endTimeInput), newTime);
        }
    });

    return DatepairView;
});
