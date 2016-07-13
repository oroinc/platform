define(function(require) {
    'use strict';

    var dateTimePickerViewMixin;
    var $ = require('jquery');
    var _ = require('underscore');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var localeSettings = require('orolocale/js/locale-settings');
    require('oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    var TIMEPICKER_DROPDOWN_CLASS_NAME = 'timepicker-dialog-is-below';
    var TIMEPICKER_DROPUP_CLASS_NAME = 'timepicker-dialog-is-above';
    var DATEPICKER_DROPDOWN_CLASS_NAME = 'ui-datepicker-dialog-is-below';
    var DATEPICKER_DROPUP_CLASS_NAME = 'ui-datepicker-dialog-is-above';

    /**
     * Mixin with prototype of TimePickerView implementation
     * (is used to extend some DatePickerView with timepicker functionality)
     * @interface TimePickerView
     */
    dateTimePickerViewMixin = {
        defaults: {
            fieldsWrapper: '<div class="fields-row"></div>',
            timeInputAttrs: {},
            timePickerOptions: {}
        },

        /**
         * Format of time that native date input accepts
         */
        nativeTimeFormat: 'HH:mm',

        /**
         * Format of date/datetime that original input accepts
         */
        backendFormat: datetimeFormatter.getBackendDateTimeFormat(),

        /**
         * @type {string}
         */
        timezone: localeSettings.getTimeZone(),

        /**
         * Returns supper prototype
         *
         * @returns {Object}
         * @protected
         */
        _super: function() {
            throw new Error('_super() should be defined');
        },

        /**
         * Initializes variable-date-time-picker view
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['timezone']));
            this._super().initialize.apply(this, arguments);
        },

        /**
         * Cleans up HTML
         *  - destroys picker widget
         *  - removes front field
         *  - unwrap original field
         *
         * @override
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (!this.nativeMode) {
                this.destroyTimePickerWidget();
            }
            this.$frontTimeField.off().remove();
            if (this.$frontDateField.data('isWrapped')) {
                this.$frontDateField.unwrap();
            }
            this._super().dispose.apply(this, arguments);
        },

        /**
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function(options) {
            this._super().createFrontField.call(this, options);
            if (options.fieldsWrapper) {
                this.$frontDateField
                    .wrap(options.fieldsWrapper)
                    .data('isWrapped', true);
            }
            this.$frontTimeField = $('<input />');
            options.timeInputAttrs.type = this.nativeMode ? 'time' : 'text';
            this.$frontTimeField.attr(options.timeInputAttrs);
            this.$frontTimeField.attr('data-fake-front-field', '');
            this.$frontTimeField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$frontDateField.on('blur', function(e) {
                $(this).parent().removeClass(DATEPICKER_DROPDOWN_CLASS_NAME + ' ' + DATEPICKER_DROPUP_CLASS_NAME);
            }).on('datepicker:dialogReposition', function(e, position) {
                $(this).parent()
                    .toggleClass(DATEPICKER_DROPDOWN_CLASS_NAME, position === 'below')
                    .toggleClass(DATEPICKER_DROPUP_CLASS_NAME, position !== 'below');
            });
            this.$frontDateField.after(this.$frontTimeField);
        },

        /**
         * Initializes date and time pickers widget
         *
         * @param {Object} options
         */
        initPickerWidget: function(options) {
            var widgetOptions = options.timePickerOptions;
            this.$frontTimeField.timepicker(widgetOptions);
            this.$frontTimeField.on('showTimepicker', function() {
                var $el = $(this);
                var isAbove = $el.data('timepicker-list').hasClass('ui-timepicker-positioned-top');
                $el.parent()
                    .toggleClass(TIMEPICKER_DROPDOWN_CLASS_NAME, !isAbove)
                    .toggleClass(TIMEPICKER_DROPUP_CLASS_NAME, isAbove);
            });
            this.$frontTimeField.on('hideTimepicker', function() {
                $(this).parent().removeClass(TIMEPICKER_DROPDOWN_CLASS_NAME + ' ' + TIMEPICKER_DROPUP_CLASS_NAME);
            });
            this.$frontDateField.on('blur', function() {
                if ($(this).hasClass('error')) {
                    $(this).parent().removeClass('timepicker-error');
                }
            });
            this.$frontTimeField.on('blur', function() {
                $(this).parent().toggleClass('timepicker-error', $(this).hasClass('error'));
            });
            if (this.$el.attr('disabled') || this.$el.attr('readonly')) {
                this.$frontTimeField.prop('disabled', true);
            }
            this._super().initPickerWidget.apply(this, arguments);
        },

        /**
         * Returns timepicker popup
         *
         * @returns {JQuery}
         */
        getTimePickerWidget: function() {
            return this.$frontTimeField.data('timepicker-list');
        },

        /**
         * Destroys picker widget
         */
        destroyTimePickerWidget: function() {
            this.$frontTimeField.timepicker('remove');
        },

        /**
         * Updates original field on front field change
         *
         * @param {jQuery.Event} e
         */
        updateOrigin: function(e) {
            this.checkConsistency(e.target);
            this._super().updateOrigin.apply(this, arguments);
            this.updateTimeFieldState();
        },

        /**
         * Update front date and time fields values
         */
        updateFront: function() {
            if (this._preventFrontendUpdate) {
                return;
            }
            this._super().updateFront.call(this);
            this.$frontTimeField.val(this.getFrontendFormattedTime());
            this.updateTimeFieldState();
        },

        /**
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param {HTMLElement} target
         */
        checkConsistency: function(target) {
            var date = this.$frontDateField.val();
            var time = this.$frontTimeField.val();
            var isValidDate = moment(date, this.getDateFormat(), true).isValid();
            var isValidTime = moment(time, this.getTimeFormat(), true).isValid();

            // just changed the date
            if (this.$frontDateField.is(target) && isValidDate && !time) {
                // default time is beginning of the day
                time = moment('00:00', 'HH:mm').format(this.getTimeFormat());
                this.$frontTimeField.val(time);

            // just changed the time
            } else if (this.$frontTimeField.is(target) && isValidTime && !date) {
                // default day is today
                date = moment().format(this.getDateFormat());
                this.$frontDateField.val(date);
            }
        },

        /**
         * Updates state of time field
         * (might be defined in the extend)
         */
        updateTimeFieldState: $.noop,

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedTime: function() {
            var value = '';
            var momentInstance = this.getOriginalMoment();
            if (momentInstance) {
                value = momentInstance.tz(this.timezone).format(this.getTimeFormat());
            }
            return value;
        },

        /**
         * Creates moment object for frontend field
         *
         * @returns {moment}
         */
        getFrontendMoment: function() {
            var date = this.$frontDateField.val();
            var time = this.$frontTimeField.val();
            var value = date + this.getSeparatorFormat() + time;
            var format = this.getDateTimeFormat();
            var momentInstance = moment.utc(value, format, true);
            if (momentInstance.isValid()) {
                return momentInstance.tz(this.timezone, true);
            }
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            var value = '';
            var momentInstance = this.getFrontendMoment();
            var format = _.isArray(this.backendFormat) ? this.backendFormat[0] : this.backendFormat;
            if (momentInstance) {
                value = momentInstance.utc().format(format);
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function() {
            var value = '';
            var momentInstance = this.getOriginalMoment();
            if (momentInstance) {
                value = momentInstance.tz(this.timezone).format(this.getDateFormat());
            }
            return value;
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
         * Defines frontend format for datetime separator
         *
         * @returns {string}
         */
        getSeparatorFormat: function() {
            return this.nativeMode ? ' ' : datetimeFormatter.getDateTimeFormatSeparator();
        },

        /**
         * Defines frontend format for datetime field
         *
         * @returns {string}
         */
        getDateTimeFormat: function() {
            var dateFormat = this.getDateFormat();
            var timeFormat = this.getTimeFormat();
            var separatorFormat = this.getSeparatorFormat();
            return dateFormat + separatorFormat + timeFormat;
        }
    };

    return dateTimePickerViewMixin;
});
