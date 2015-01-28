define(function (require) {
    'use strict';

    var TimePickerViewPrototype,
        $ = require('jquery'),
        _ = require('underscore');
    require('oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    /**
     * Mixin with prototype of TimePickerView implementation
     * (is used to extend some DatePickerView with timepicker functionality)
     * @interface TimePickerView
     */
    TimePickerViewPrototype = {
        defaults: {
            fieldsWrapper: '',
            timeInputAttrs: {},
            timePickerOptions: {}
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
        createFrontField: function (options) {
            this._super().createFrontField.call(this, options);
            if (options.fieldsWrapper) {
                this.$frontDateField
                    .wrap(options.fieldsWrapper)
                    .data('isWrapped', true);
            }
            this.$frontTimeField = $('<input />');
            options.timeInputAttrs.type = this.nativeMode ? 'time' : 'text';
            this.$frontTimeField.attr(options.timeInputAttrs);
            this.$frontTimeField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$frontDateField.after(this.$frontTimeField);
        },

        /**
         * Initializes date and time pickers widget
         *
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            var widgetOptions = options.timePickerOptions;
            this.$frontTimeField.timepicker(widgetOptions);
            this._super().initPickerWidget.apply(this, arguments);
        },

        /**
         * Destroys picker widget
         */
        destroyTimePickerWidget: function () {
            this.$frontTimeField.timepicker('remove');
        },

        /**
         * Updates original field on front field change
         */
        updateOrigin: function () {
            this._super().updateOrigin.apply(this, arguments);
            this.updateTimeFieldState();
        },

        /**
         * Update front date and time fields values
         */
        updateFront: function () {
            this._super().updateFront.call(this);
            this.$frontTimeField.val(this.getFrontendFormattedTime());
            this.updateTimeFieldState();
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
        getFrontendFormattedTime: function () {
            // should be overridden in the extend
            return this.$el.val();
        }
    };

    return TimePickerViewPrototype;
});
