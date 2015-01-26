/*global define*/
define(function (require) {
    'use strict';

    var VariableDatePickerView,
        _ = require('underscore'),
        TabsView = require('./tabs-view'),
        DateVariableHelper = require('orofilter/js/date-variable-helper'),
        DatePickerView = require('oroui/js/app/views/datepicker/datepicker-view');
    require('orofilter/js/datevariables-widget');

    VariableDatePickerView = DatePickerView.extend({
        defaultTabs: [
            {
                name: 'calendar',
                label: 'oro.filter.date.tab.calendar'
            },
            {
                name: 'variables',
                label: 'oro.filter.date.tab.variables'
            }
        ],

        /**
         * Initializes variable-date-picker view
         * @param {Object} options
         */
        initialize: function (options) {
            this.dateVariableHelper = new DateVariableHelper(options.datePickerOptions.dateVars);
            VariableDatePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Updates part of variable picker
         *
         * @param {string} part
         * @returns {VariableDatePickerView}
         */
        setPart: function (part) {
            this.$variables.dateVariables('setPart', part);
            return this;
        },

        /**
         * Initializes date picker widget
         *  - tab view
         *  - date picker
         *  - variable picker
         *
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            this.initTabsView(options);
            this.initDatePicker(options);
            this.initVariablePicker(options);
        },

        /**
         * Initializes tab view
         *
         * @param {Object} options
         */
        initTabsView: function (options) {
            var tabs;
            this.$dropdown = this.$frontDateField
                .wrap('<div class="dropdown datefilter">').parent();
            tabs = new TabsView({
                el: this.$dropdown,
                template: options.dropdownTemplate,
                data: {
                    tabs: options.tabs || this.defaultTabs,
                    suffix: '-' + this.cid
                }
            });
            this.subview('tabs', tabs);
            this.$frontDateField.on('focus, click', _.bind(this.open, this));
        },

        /**
         * Initializes date picker widget
         *
         * @param {Object} options
         */
        initDatePicker: function (options) {
            var widgetOptions = {};
            this.$calendar = this.$dropdown.find('#calendar-' + this.cid);
            _.extend(widgetOptions, options.datePickerOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$calendar.datepicker(widgetOptions);
            this.$calendar.addClass(widgetOptions.className)
                .click(function (e) {
                    e.stopImmediatePropagation();
                });
        },

        /**
         * Initializes variable picker widget
         *
         * @param {Object} options
         */
        initVariablePicker: function (options) {
            var widgetOptions = {};
            _.extend(widgetOptions, options.datePickerOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$variables = this.$dropdown.find('#variables-' + this.cid);
            this.$variables.dateVariables(widgetOptions);
            this.$variables.addClass(widgetOptions.className);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function () {
            this.$calendar.datepicker('destroy');
            this.$calendar.off();
            this.$variables.dateVariables('destroy');
            this.subview('tabs').dispose();
            this.$frontDateField.unwrap();
            delete this.$calendar;
            delete this.$variables;
            delete this.$dropdown;
        },

        /**
         * Handles pick date event
         */
        onSelect: function (date) {
            this.$frontDateField.val(date);
            VariableDatePickerView.__super__.onSelect.apply(this, arguments);
            this.close();
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else {
                value = VariableDatePickerView.__super__.getBackendFormattedValue.call(this);
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function () {
            var value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else {
                value = VariableDatePickerView.__super__.getFrontendFormattedDate.call(this);
            }
            return value;
        },

        /**
         * Opens dropdown with date-picker + variable-picker
         */
        open: function () {
            this.$dropdown.addClass('open');
            var value = this.$frontDateField.val();
            if (!this.dateVariableHelper.isDateVariable(value)) {
                this.$calendar.datepicker('setDate', value);
            } else {
                // open variable tab
                this.subview('tabs').show('variables');
            }
            this.$calendar.datepicker('refresh');
            this.trigger('open', this);
        },

        /**
         * Closes dropdown with date-picker + variable-picker
         */
        close: function () {
            this.$dropdown.removeClass('open');
            this.trigger('close', this);
        }
    });

    return VariableDatePickerView;
});
