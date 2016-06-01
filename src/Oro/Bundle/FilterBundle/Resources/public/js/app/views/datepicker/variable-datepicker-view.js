define(function(require) {
    'use strict';

    var VariableDatePickerView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var TabsView = require('oroui/js/app/views/tabs-view');
    var DateVariableHelper = require('orofilter/js/date-variable-helper');
    var DateValueHelper = require('orofilter/js/date-value-helper');
    var DatePickerView = require('oroui/js/app/views/datepicker/datepicker-view');
    var moment = require('moment');
    var localeSettings = require('orolocale/js/locale-settings');
    require('orofilter/js/datevariables-widget');
    require('orofilter/js/itemizedpicker-widget');

    function isBetween(value, min, max) {
        value = parseInt(value);

        return !_.isNaN(value) && value >= min && value <= max;
    }

    function findKey(map, value) {
        var foundKey;
        _.each(map, function(item, key) {
            if (item === value) {
                foundKey = key;
            }
        });

        return foundKey;
    }

    VariableDatePickerView = DatePickerView.extend({
        defaultTabs: [],

        partsDateValidation: {
            value: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    this.dateValueHelper.isValid(date) ||
                    moment(date, this.getDateFormat(), true).isValid();
            },
            dayofweek: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    findKey(localeSettings.getCalendarDayOfWeekNames('wide'), date);
            },
            week: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    isBetween(date, 1, 53);
            },
            day: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    isBetween(date, 1, 31);
            },
            month: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    findKey(localeSettings.getCalendarMonthNames('wide'), date);
            },
            quarter: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    isBetween(date, 1, 4);
            },
            dayofyear: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    isBetween(date, 1, 365);
            },
            year: function(date) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    !_.isNaN(parseInt(date));
            }
        },

        /**
         * Initializes variable-date-picker view
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaultTabs = [
                {
                    name: 'calendar',
                    label: __('oro.filter.date.tab.calendar'),
                    isVisible: _.bind(function() {
                        return this.$variables.dateVariables('getPart') === 'value';
                    }, this)
                },
                {
                    name: 'days',
                    label: __('oro.filter.date.tab.days'),
                    isVisible: _.bind(function() {
                        return this.$variables.dateVariables('getPart') === 'dayofweek';
                    }, this)
                },
                {
                    name: 'months',
                    label: __('oro.filter.date.tab.months'),
                    isVisible: _.bind(function() {
                        return this.$variables.dateVariables('getPart') === 'month';
                    }, this)
                },
                {
                    name: 'variables',
                    label: __('oro.filter.date.tab.variables')
                }
            ];

            this.dateVariableHelper = new DateVariableHelper(options.datePickerOptions.dateVars);
            this.dateValueHelper = new DateValueHelper(options.dayFormats);
            VariableDatePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Updates part of variable picker
         *
         * @param {string} part
         */
        setPart: function(part) {
            this.$variables.dateVariables('setPart', part);
            this.$frontDateField.attr('placeholder', __('oro.filter.date.placeholder.' + part));
        },

        /**
         * Initializes date picker widget
         *  - tab view
         *  - date picker
         *  - variable picker
         *
         * @param {Object} options
         */
        initPickerWidget: function(options) {
            this.initTabsView(options);
            this.initDatePicker(options);
            this.initVariablePicker(options);
            this.initItemizedPicker(
                'days',
                __('oro.filter.date.days.title'),
                localeSettings.getSortedDayOfWeekNames('wide')
            );
            this.initItemizedPicker(
                'months',
                __('oro.filter.date.months.title'),
                localeSettings.getCalendarMonthNames('wide')
            );
        },

        initItemizedPicker: function(tabName, title, items) {
            this.$dropdown.find('#' + tabName + '-' + this.cid).itemizedPicker({
                title: title,
                items: items,
                onSelect: _.bind(this.onSelect, this)
            });
        },

        /**
         * Initializes tab view
         *
         * @param {Object} options
         */
        initTabsView: function(options) {
            var tabs;
            this.$dropdown = this.$frontDateField
                .wrap('<div class="dropdown datefilter">').parent();
            this.$dropdown.append('<div class="dropdown-menu dropdown-menu-calendar test"></div>');
            tabs = new TabsView({
                el: this.$dropdown.find('.dropdown-menu'),
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
        initDatePicker: function(options) {
            var widgetOptions = {};
            this.$calendar = this.$dropdown.find('#calendar-' + this.cid);
            _.extend(widgetOptions, options.datePickerOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$calendar.datepicker(widgetOptions);
            this.$calendar.addClass(widgetOptions.className)
                .click(function(e) {
                    e.stopImmediatePropagation();
                });
        },

        /**
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param target
         */
        checkConsistency: function(target) {
            var date = this.$frontDateField.val();
            if (!this._preventFrontendUpdate && !target && !this._isDateValid(date)) {
                this.$frontDateField.val('');
            }
        },

        _isDateValid: function(date) {
            var part = this.$variables.dateVariables('getPart');
            var validator = this.partsDateValidation[part];
            if (!validator) {
                return false;
            }

            return validator.call(this, date);
        },

        /**
         * Initializes variable picker widget
         *
         * @param {Object} options
         */
        initVariablePicker: function(options) {
            var widgetOptions = {};
            _.extend(widgetOptions, options.datePickerOptions, {
                onSelect: _.bind(this.onSelect, this)
            });
            this.$variables = this.$dropdown.find('#variables-' + this.cid);
            this.$variables.dateVariables(widgetOptions);
            this.$frontDateField.attr(
                'placeholder',
                __('oro.filter.date.placeholder.' + this.$variables.dateVariables('getPart'))
            );
            this.$variables.addClass(widgetOptions.className);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function() {
            this.$calendar.datepicker('destroy');
            this.$calendar.off();
            this.$variables.dateVariables('destroy');
            this.removeSubview('tabs');
            this.$frontDateField.unwrap();
            delete this.$calendar;
            delete this.$variables;
            delete this.$dropdown;
        },

        /**
         * Handles pick date event
         */
        onSelect: function(date) {
            this.$frontDateField.val(date);
            VariableDatePickerView.__super__.onSelect.apply(this, arguments);
            this.close();
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            var value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatRawValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value) ?
                    this.dateValueHelper.formatRawValue(value) :
                    VariableDatePickerView.__super__.getBackendFormattedValue.call(this);
            }

            return this.getBackendPartFormattedValue();
        },

        getBackendPartFormattedValue: function() {
            var value = this.$frontDateField.val();

            switch (this.$variables.dateVariables('getPart')) {
                case 'dayofweek':
                    return findKey(localeSettings.getCalendarDayOfWeekNames('wide'), value);
                case 'month':
                    return findKey(localeSettings.getCalendarMonthNames('wide'), value);
            }

            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function() {
            var value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatDisplayValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value) ?
                    this.dateValueHelper.formatDisplayValue(value) :
                    VariableDatePickerView.__super__.getFrontendFormattedDate.call(this);
            }

            return this.getFrontendPartFormattedDate();
        },

        getFrontendPartFormattedDate: function() {
            var value = this.$el.val();
            switch (this.$variables.dateVariables('getPart')) {
                case 'dayofweek':
                    return localeSettings.getCalendarDayOfWeekNames('wide')[value];
                case 'month':
                    return localeSettings.getCalendarMonthNames('wide')[value];
            }

            return value;
        },

        /**
         * Opens dropdown with date-picker + variable-picker
         */
        open: function() {
            this.$dropdown.addClass('open');
            var value = this.$frontDateField.val();
            if (!this.dateVariableHelper.isDateVariable(value)) {
                this.$calendar.datepicker('setDate', value);
            }

            this.subview('tabs').updateTabsVisibility();
            if (this.dateVariableHelper.isDateVariable(value)) {
                this.subview('tabs').show('variables');
            }
            this.$calendar.datepicker('refresh');
            this.trigger('open', this);
        },

        /**
         * Closes dropdown with date-picker + variable-picker
         */
        close: function() {
            this.$dropdown.removeClass('open');
            this.trigger('close', this);
        }
    });

    return VariableDatePickerView;
});
