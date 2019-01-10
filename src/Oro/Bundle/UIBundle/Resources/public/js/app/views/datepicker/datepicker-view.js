define(function(require) {
    'use strict';

    var DatePickerView;
    var $ = require('jquery');
    var _ = require('underscore');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');

    DatePickerView = BaseView.extend({
        defaults: {
            dateInputAttrs: {
                autocomplete: 'off'
            },
            datePickerOptions: {}
        },

        events: {
            change: 'onOriginChange'
        },

        /**
         * Use native pickers of proper HTML-inputs
         */
        nativeMode: false,

        /**
         * Format of date that native date input accepts
         */
        nativeDateFormat: 'YYYY-MM-DD',

        /**
         * Format of date/datetime that original input accepts
         */
        backendFormat: datetimeFormatter.getBackendDateFormat(),

        /**
         * Flag to prevent frontend field update once origin field is changed
         *
         * e.g. user manually enters date and it is temporary invalid:
         *  - origin field gets empty value (no valid value entered yet)
         *  - frontend field has not finished value and user keeps changing it
         */
        _preventFrontendUpdate: false,

        /**
         * ClassName for empty field
         */
        emptyClassName: 'input--empty',

        /**
         * @inheritDoc
         */
        constructor: function DatePickerView() {
            DatePickerView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initializes view
         *  - creates front field
         *  - updates front field
         *  - initializes picker widget
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var opts = {};
            $.extend(true, opts, this.defaults, options);
            $.extend(this, _.pick(opts, ['nativeMode', 'backendFormat']));

            this.createFrontField(opts);

            if (this.$el[0].type !== 'hidden') {
                this.$el.wrap('<span style="display:none"></span>');
            }
            if (!this.nativeMode) {
                this.initPickerWidget(opts);
            }

            if (this.$el.val() && this.$el.val().length) {
                this.updateFront();
            }

            DatePickerView.__super__.initialize.apply(this, arguments);
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
                this.destroyPickerWidget();
            }
            this.$frontDateField.off().remove();
            this.$el.unwrap();
            DatePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Sets value directly to backend field
         *
         * @param {string} value
         */
        setValue: function(value) {
            if (this.$el.val() !== value) {
                this.$el.val(value).trigger('change');
            }
        },

        /**
         * Sets `disabled` property directly to backend field and to datepicker widget
         *
         * @param {boolean} disabled
         */
        setDisabled: function(disabled) {
            var event = disabled ? 'disabled' : 'enabled';
            this.$el.prop('disabled', disabled).trigger(event);
            this.$frontDateField.datepicker(disabled ? 'disable' : 'enable').trigger(event);
        },

        /**
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function(options) {
            this.$frontDateField = $('<input />');
            options.dateInputAttrs.type = this.nativeMode ? 'date' : 'text';
            this.$frontDateField.attr(options.dateInputAttrs);
            this.$frontDateField.attr('data-fake-front-field', '');
            this.$frontDateField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$frontDateField.on('keypress keyup change focus blur', _.bind(this.checkEmpty, this));
            this.checkEmpty();
            this.$el.after(this.$frontDateField);
            this.$el.attr('data-format', 'backend');
        },

        /**
         * Initializes date picker widget
         *
         * @param {Object} options
         */
        initPickerWidget: function(options) {
            var widgetOptions = options.datePickerOptions;
            _.extend(widgetOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$frontDateField.datepicker(widgetOptions);
            // fix incorrect behaviour with early datepicker dispose
            $('#ui-datepicker-div').css({display: 'none'});
            if (this.$el.attr('disabled') || this.$el.attr('readonly')) {
                this.$frontDateField.datepicker('disable');
            }
        },

        /**
         * Returns datepicker popup
         *
         * @returns {JQuery}
         */
        getDatePickerWidget: function() {
            return this.$frontDateField.datepicker('widget');
        },

        setMinValue: function(minValue) {
            this.$frontDateField.datepicker('option', 'minDate', minValue);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function() {
            if (!this.$frontDateField.data('datepicker')) {
                // field was removed from DOM and there are no data to retrieve the instance
                return;
            }
            this.$frontDateField.datepicker('destroy');
        },

        /**
         * Handles pick date event
         */
        onSelect: function() {
            var form = this.$frontDateField.parents('form');
            if (form.length && form.data('validator')) {
                form.validate()
                    .element(this.$frontDateField);
            }
            this.$frontDateField.trigger('change');
        },

        /**
         * Update empty state
         */
        checkEmpty: function() {
            if (this.nativeMode && this.$frontDateField) {
                this.$frontDateField.toggleClass(this.emptyClassName, !this.$frontDateField.val().length);
            }
        },

        /**
         * Updates original field on front field change
         *
         * @param {jQuery.Event} e
         */
        updateOrigin: function(e) {
            var backendFormattedValue = this.getBackendFormattedValue();
            if (!_.isUndefined(backendFormattedValue) && this.$el.val() !== backendFormattedValue) {
                this._preventFrontendUpdate = true;
                this.$el.val(backendFormattedValue).trigger('change');
                this._preventFrontendUpdate = false;
            }
        },

        onOriginChange: function() {
            this.updateFront();
            var form = this.$el.closest('form');
            if (form.length && form.data('validator')) {
                form.validate()
                    .element(this.$el);
            }
        },

        /**
         * Update front date field value
         */
        updateFront: function() {
            if (this._preventFrontendUpdate) {
                return;
            }
            this.$frontDateField.val(this.getFrontendFormattedDate());
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            var momentInstance = this.getFrontendMoment();
            var format = _.isArray(this.backendFormat) ? this.backendFormat[0] : this.backendFormat;
            if (momentInstance) {
                return momentInstance.utc().format(format);
            } else if (momentInstance === null) {
                return '';
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
                value = momentInstance.format(this.getDateFormat());
            }
            return value;
        },

        /**
         * Creates moment object for original field
         *
         * @returns {moment}
         */
        getOriginalMoment: function() {
            var value = this.$el.val();
            var format = this.backendFormat;
            var momentInstance = moment.utc(value, format, true);
            if (momentInstance.isValid()) {
                return momentInstance;
            }
        },

        /**
         * Creates moment object for frontend field
         *
         * @returns {moment}
         */
        getFrontendMoment: function() {
            var value = this.$frontDateField.val();

            if (_.isEmpty(_.trim(value))) {
                return null;
            }

            var format = this.getDateFormat();
            var momentInstance = moment.utc(value, format, true);
            if (momentInstance.isValid()) {
                return momentInstance;
            }
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

    return DatePickerView;
});
