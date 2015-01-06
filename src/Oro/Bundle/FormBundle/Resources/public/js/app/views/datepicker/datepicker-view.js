define(function (require) {
    'use strict';

    var DatePickerView,
        $ = require('jquery'),
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');

    DatePickerView = BaseView.extend({
        defaults: {
            useNativePicker: false,
            dateInputAttrs: {},
            datePickerOptions: {}
        },

        events: {
            change: 'updateFront'
        },

        /**
         * Initializes view
         *  - creates front field
         *  - updates front field
         *  - initializes picker widget
         *
         * @param {Object} options
         */
        initialize: function (options) {
            var opts = {};
            $.extend(true, opts, this.defaults, options);
            this.nativeMode = opts.useNativePicker;

            this.createFrontField(opts);

            this.$el.wrap('<span style="display:none"></span>');
            if (this.$el.val() && this.$el.val().length) {
                this.updateFront();
            }

            if (!this.nativeMode) {
                this.initPickerWidget(opts);
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
        dispose: function () {
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
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function (options) {
            this.$frontDateField = $('<input />');
            options.dateInputAttrs.type = this.nativeMode ? 'date' : 'text';
            this.$frontDateField.attr(options.dateInputAttrs);
            this.$frontDateField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$el.after(this.$frontDateField);
        },

        /**
         * Initializes date picker widget
         * 
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            var widgetOptions = options.datePickerOptions;
            _.extend(widgetOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$frontDateField.datepicker(widgetOptions);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function () {
            // @TODO fix the bug BAP-7121
            this.$frontDateField.datepicker('destroy');
        },

        /**
         * Handles pick date event
         */
        onSelect: function () {
            var form = this.$frontDateField.parents('form');
            if (form.length && form.data('validator')) {
                form.validate()
                    .element(this.$frontDateField);
            }
            this.$frontDateField.trigger('change');
        },

        /**
         * Updates original field on front field change
         */
        updateOrigin: function () {
            this.$el.val(this.getBackendFormattedValue());
        },

        /**
         * Update front date field value
         */
        updateFront: function () {
            this.$frontDateField.val(this.getFrontendFormattedDate());
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value = this.$frontDateField.val();
            if (this.nativeMode) {
                // nothing to do, it's already suppose to be in 'yyyy-mm-dd' format
            } else if (datetimeFormatter.isDateValid(value)) {
                value = datetimeFormatter.convertDateToBackendFormat(value);
            } else {
                value = '';
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function () {
            var value = datetimeFormatter.formatDate(this.$el.val());
            return value;
        }
    });

    return DatePickerView;
});
