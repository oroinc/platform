define(function (require) {
    'use strict';

    var DatePickerView,
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');

    DatePickerView = BaseView.extend({
        type: 'date',

        events: {
            change: 'sync'
        },

        /**
         * Initializes view
         *  - creates front field
         *  - updates original field
         *  - initializes picker widget
         *
         * @param {Object} options
         */
        initialize: function (options) {
            var widgetOptions;

            this.createFrontField();

            this.$el.wrap('<span style="display:none"></span>');
            if (this.$el.val() && this.$el.val().length) {
                this.sync();
            }

            widgetOptions = _.clone(options);
            _.extend(widgetOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.initPickerWidget(widgetOptions);

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
            this.destroyPickerWidget();
            this.$frontField.off().remove();
            this.$el.unwrap();
            DatePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Creates frontend field
         */
        createFrontField: function () {
            this.$frontField = this.$el.clone();
            this.$frontField.attr({
                type: 'text',
                id: this.type + '_selector_' + this.$el.attr('id'),
                name: this.type + '_selector_' + this.$el.attr('id')
            });
            this.$frontField.on('keyup', _.bind(this.onKeyup, this));
            this.$el.after(this.$frontField);
        },

        /**
         * Initializes picker widget
         * 
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            this.$frontField.datepicker(options);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function () {
            // @TODO fix the bug BAP-7121
            this.$frontField.datepicker('destroy');
        },

        /**
         * Handles keyup event on front field and updates original field
         */
        onKeyup: function () {
            this.$el.val(this.getBackendFormattedValue());
        },

        /**
         * Handles pick date event
         */
        onSelect: function () {
            var form = this.$frontField.parents('form');
            if (form.length && form.data('validator')) {
                form.validate()
                    .element(this.$frontField);
            }
            this.$frontField.trigger('change');
        },

        /**
         * Update front field value
         */
        sync: function () {
            this.$frontField.val(this.getFrontendFormattedValue());
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value = this.$frontField.val();
            if (datetimeFormatter.isDateValid(value)) {
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
        getFrontendFormattedValue: function () {
            var value = datetimeFormatter.formatDate(this.$el.val());
            return value;
        }
    });

    return DatePickerView;
});
