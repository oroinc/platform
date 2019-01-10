define(function(require) {
    'use strict';

    var dateTimePickerViewMixin;
    var $ = require('jquery');
    var _ = require('underscore');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var localeSettings = require('orolocale/js/locale-settings');
    require('jquery.timepicker');

    var TIMEPICKER_DROPDOWN_CLASS_NAME = 'timepicker-dialog-is-below';
    var TIMEPICKER_DROPUP_CLASS_NAME = 'timepicker-dialog-is-above';
    var DATEPICKER_DROPDOWN_CLASS_NAME = 'ui-datepicker-dialog-is-below';
    var DATEPICKER_DROPUP_CLASS_NAME = 'ui-datepicker-dialog-is-above';

    /**
     * Checks if parent form has validator and runs validation
     *
     * @param {jQuery} $field
     */
    function validateFieldSafely($field) {
        var $form = $field.closest('form');

        if ($form.length && $form.data('validator')) {
            $form.validate().element($field);
        }
    }

    /**
     * Mixin with prototype of TimePickerView implementation
     * (is used to extend some DatePickerView with timepicker functionality)
     * @interface TimePickerView
     */
    dateTimePickerViewMixin = {
        defaults: {
            fieldsWrapper: '<div class="fields-row"></div>',
            timeInputAttrs: {
                autocomplete: 'off'
            },
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
         * @type {string|function}
         */
        defaultTime: null,

        /**
         * ClassName for empty field
         */
        emptyClassName: 'input--empty',

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
            _.extend(this, _.pick(options, ['timezone', 'defaultTime']));
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
                this.$frontDateField.removeData('isWrapped');
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
                    .data('isWrapped', true)
                    .before(this.$el);
            }
            this.$frontTimeField = $('<input />');
            options.timeInputAttrs.type = this.nativeMode ? 'time' : 'text';
            this.$frontTimeField.attr(options.timeInputAttrs);
            this.$frontTimeField.attr('data-fake-front-field', '');
            this.$frontTimeField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$frontTimeField.on('keypress keyup change focus blur', _.bind(this.checkEmpty, this));
            this.checkEmpty();
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

        setDisabled: function(disabled) {
            this.$frontTimeField.prop('disabled', disabled).trigger(disabled ? 'disabled' : 'enabled');
            this._super().setDisabled.apply(this, arguments);
        },

        /**
         * Returns timepicker popup
         *
         * @returns {jQuery}
         */
        getTimePickerWidget: function() {
            return this.$frontTimeField.data('timepicker-list');
        },

        /**
         * Destroys picker widget
         */
        destroyTimePickerWidget: function() {
            if (!this.$frontTimeField.data('timepicker-settings')) {
                // the widget was already removed.
                return;
            }
            // this will trigger hide event before remove
            // that is not done by default implementation
            this.$frontTimeField.timepicker('hide');
            this.$frontTimeField.timepicker('remove');
        },

        /**
         * Update empty state
         */
        checkEmpty: function() {
            this._super().checkEmpty.apply(this, arguments);

            if (this.nativeMode && this.$frontTimeField) {
                this.$frontTimeField.toggleClass(this.emptyClassName, !this.$frontTimeField.val().length);
            }
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
            validateFieldSafely(this.$frontTimeField);
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
                time = this.getDefaultTime();
                this.$frontTimeField.val(time);
                validateFieldSafely(this.$frontTimeField);
            // just changed the time
            } else if (this.$frontTimeField.is(target) && isValidTime && !date) {
                // default day is today
                date = moment().format(this.getDateFormat());
                this.$frontDateField.val(date);
                validateFieldSafely(this.$frontDateField);
            }
        },

        /**
         * Calculates default time to fill in a correspondent field if only date field was selected
         *
         * @returns {string}
         */
        getDefaultTime: function() {
            var date;
            var todayDate;
            var currentTimeMoment;
            var guessTimeMoment;
            var time = _.result(this, 'defaultTime');
            if (!time) {
                date = this.$frontDateField.val();
                todayDate = moment().tz(this.timezone).format(this.getDateFormat());
                if (date === todayDate) {
                    currentTimeMoment = moment().tz(this.timezone);
                    // add 15 minutes to current time if it's today date
                    guessTimeMoment = currentTimeMoment.clone().add(15, 'minutes');
                    // round up till 5 minutes
                    guessTimeMoment.add(5 - (guessTimeMoment.minute() % 5 || 5), 'minutes');
                    if (guessTimeMoment.diff(currentTimeMoment.clone().set('hour', 23).set('minute', 59)) < 0) {
                        // if it is still same date
                        time = guessTimeMoment.format('HH:mm');
                    } else {
                        // set max available time for today
                        time = '23:59';
                    }
                } else {
                    // default time is beginning of the day
                    time = '00:00';
                }
            }
            return moment(time, 'HH:mm').format(this.getTimeFormat());
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
            if (_.isEmpty(_.trim(date + time))) {
                return null;
            }
            var value = date + this.getSeparatorFormat() + time;
            var format = this.getDateTimeFormat();
            var momentInstance = moment.utc(value, format, true);
            if (momentInstance.isValid()) {
                return momentInstance.tz(this.timezone, true);
            }
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
