define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const moment = require('moment');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');
    const BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui/widgets/datepicker');

    const DatePickerView = BaseView.extend({
        defaults: {
            dateInputAttrs: {
                'autocomplete': 'off',
                'aria-label': __('oro.ui.datepicker.aria_label')
            },
            datePickerOptions: {
                isRTL: _.isRTL()
            }
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
         * @inheritdoc
         */
        constructor: function DatePickerView(options) {
            DatePickerView.__super__.constructor.call(this, options);
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
            const opts = {};
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

            DatePickerView.__super__.initialize.call(this, options);
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
            DatePickerView.__super__.dispose.call(this);
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
            const event = disabled ? 'disabled' : 'enabled';
            this.$el.prop('disabled', disabled).trigger(event);
            this.$frontDateField.datepicker(disabled ? 'disable' : 'enable').trigger(event);
        },

        /**
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function(options) {
            // According to accessibility if input doesn't linked label
            if ($(`label[for="${options.dateInputAttrs.id}"]`).length) {
                delete options.dateInputAttrs['aria-label'];
            }

            this.$frontDateField = $('<input />');
            options.dateInputAttrs.type = this.nativeMode ? 'date' : 'text';
            this.$frontDateField.attr(options.dateInputAttrs);
            this.$frontDateField.attr('data-fake-front-field', '');
            this.$frontDateField.on('keyup change', this.updateOrigin.bind(this));
            this.$frontDateField.on('keypress keyup change focus blur', this.checkEmpty.bind(this));
            this.syncPickerState();
            this.checkEmpty();
            this.$el.after(this.$frontDateField);
            this.$el.attr('data-format', 'backend');

            if (options.dateInputAttrs.id === this.$el.attr('id')) {
                this.$el.attr('id', `hidden_${this.$el.attr('id')}`);
            }
        },

        /**
         * Initializes date picker widget
         *
         * @param {Object} options
         */
        initPickerWidget: function(options) {
            const widgetOptions = options.datePickerOptions;
            _.extend(widgetOptions, {
                onSelect: this.onSelect.bind(this)
            });
            this.$frontDateField.datepicker(widgetOptions);
            // fix incorrect behaviour with early datepicker dispose
            $('#ui-datepicker-div').css({display: 'none'});
        },

        /**
         * Sync enabled\disabled state with native datepicker
         */
        syncPickerState: function() {
            const state = this.$el.prop('disabled') || this.$el.prop('readonly');
            this.$frontDateField.prop('disabled', state);
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
            const form = this.$frontDateField.parents('form');
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
            const backendFormattedValue = this.getBackendFormattedValue();
            if (!_.isUndefined(backendFormattedValue) && this.$el.val() !== backendFormattedValue) {
                this._preventFrontendUpdate = true;
                this.$el.val(backendFormattedValue).trigger('change');
                this._preventFrontendUpdate = false;
            }
        },

        onOriginChange: function() {
            this.updateFront();
            const form = this.$el.closest('form');
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
            this.checkEmpty();
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            const momentInstance = this.getFrontendMoment();
            const format = _.isArray(this.backendFormat) ? this.backendFormat[0] : this.backendFormat;
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
            let value = '';
            const momentInstance = this.getOriginalMoment();
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
            const value = this.$el.val();
            const format = this.backendFormat;
            const momentInstance = moment.utc(value, format, true);
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
            const value = this.$frontDateField.val();

            if (_.isEmpty(_.trim(value))) {
                return null;
            }

            const format = this.getDateFormat();
            const momentInstance = moment.utc(value, format, true);
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
