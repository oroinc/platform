define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const TabsView = require('oroui/js/app/views/tabs-view');
    const DateVariableHelper = require('orofilter/js/date-variable-helper');
    const DateValueHelper = require('orofilter/js/date-value-helper');
    const FilterDatePickerView = require('orofilter/js/app/views/datepicker/filter-datepicker-view').default;
    const moment = require('moment');
    const localeSettings = require('orolocale/js/locale-settings');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    require('orofilter/js/datevariables-widget');
    require('orofilter/js/itemizedpicker-widget');

    function isBetween(value, min, max) {
        value = parseInt(value);

        return !_.isNaN(value) && value >= min && value <= max;
    }

    function findKey(map, value) {
        let foundKey;
        _.each(map, function(item, key) {
            if (item === value) {
                foundKey = key;
            }
        });

        return foundKey;
    }

    const VariableDatePickerView = FilterDatePickerView.extend({
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
         * @inheritdoc
         */
        constructor: function VariableDatePickerView(options) {
            VariableDatePickerView.__super__.constructor.call(this, options);
        },

        /**
         * Initializes variable-date-picker view
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaultTabs = [
                {
                    name: 'calendar',
                    icon: 'calendar',
                    label: __('oro.filter.date.tab.calendar'),
                    isVisible: () => {
                        return this.$variables.dateVariables('getPart') === 'value';
                    }
                },
                {
                    name: 'days',
                    label: __('oro.filter.date.tab.days'),
                    isVisible: () => {
                        return this.$variables.dateVariables('getPart') === 'dayofweek';
                    }
                },
                {
                    name: 'months',
                    label: __('oro.filter.date.tab.months'),
                    isVisible: () => {
                        return this.$variables.dateVariables('getPart') === 'month';
                    }
                },
                {
                    name: 'variables',
                    icon: 'list-ul',
                    label: __('oro.filter.date.tab.variables')
                }
            ];

            this.dateVariableHelper = new DateVariableHelper(options.datePickerOptions.dateVars);
            this.dateValueHelper = new DateValueHelper(options.dayFormats);
            VariableDatePickerView.__super__.initialize.call(this, options);
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
            this.initDropdown(options);
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
                onSelect: this.onSelect.bind(this)
            });
        },

        /**
         * Initializes tab view
         *
         * @param {Object} options
         */
        initTabsView: function(options) {
            const tabs = new TabsView({
                el: this.$dropdown.find('.dropdown-menu'),
                template: options.dropdownTemplate,
                data: {
                    tabs: options.tabs || this.defaultTabs,
                    suffix: '-' + this.cid
                }
            });
            this.subview('tabs', tabs);
        },

        /**
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param target
         */
        checkConsistency: function(target) {
            const date = this.$frontDateField.val();
            if (!this._preventFrontendUpdate && !this.$frontDateField.is(target) && !this._isDateValid(date)) {
                this.$frontDateField.val('');
            }
        },

        _isDateValid: function(date) {
            const part = this.$variables.dateVariables('getPart');
            const validator = this.partsDateValidation[part];
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
            const widgetOptions = {};
            _.extend(widgetOptions, options.datePickerOptions, {
                onSelect: this.onSelect.bind(this)
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
            if (this.disposed) {
                return;
            }

            VariableDatePickerView.__super__.destroyPickerWidget.call(this);
            this.$variables.dateVariables('destroy');
            delete this.$variables;
            this.removeSubview('tabs');
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            const value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatRawValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value)
                    ? this.dateValueHelper.formatRawValue(value)
                    : VariableDatePickerView.__super__.getBackendFormattedValue.call(this);
            }

            return this.getBackendPartFormattedValue();
        },

        getBackendPartFormattedValue: function() {
            const value = this.$frontDateField.val();

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
            const value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatDisplayValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value)
                    ? this.dateValueHelper.formatDisplayValue(value)
                    : VariableDatePickerView.__super__.getFrontendFormattedDate.call(this);
            }

            return this.getFrontendPartFormattedDate();
        },

        getFrontendPartFormattedDate: function() {
            const value = this.$el.val();
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
        onOpen: function(e) {
            if (e.namespace !== 'bs.dropdown') {
                // handle only events triggered with proper NS (omit just any show events)
                return;
            }
            const value = this.$frontDateField.val();
            if (!this.dateVariableHelper.isDateVariable(value)) {
                this.$calendar.datepicker('setDate', value);
            }

            this.subview('tabs').updateTabsVisibility();
            if (this.dateVariableHelper.isDateVariable(value)) {
                this.subview('tabs').show('variables');
                manageFocus.focusTabbable(this.$variables, this.$variables.find(`:contains(${value})`));
            }
            this.$calendar.datepicker('refresh');
            manageFocus.focusTabbable(this.$calendar, this.$calendar.find('.ui-datepicker-calendar'));
            this.trigger('open', this);
        },

        /**
         * Find HTML element witch will use as calendar main element
         * @return {HTMLElement}
         */
        getCalendarElement() {
            return this.$dropdown.find('#calendar-' + this.cid);
        }
    });

    return VariableDatePickerView;
});
