define(function(require) {
    'use strict';

    const AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const localeSettings = require('orolocale/js/locale-settings');
    const _ = require('underscore');
    const $ = require('jquery');

    const decimalSettings = localeSettings.getNumberFormats('decimal');
    const decimalSeparator = decimalSettings.decimal_separator_symbol;
    const groupingSeparator = decimalSettings.grouping_separator_symbol;

    const NumberInputWidgetView = AbstractInputWidgetView.extend({
        events: {
            input: '_normalizeNumberFieldValue',
            change: '_normalizeNumberFieldValue',
            keypress: '_addFraction'
        },

        precision: null,

        type: null,

        pattern: null,

        allowZero: true,

        limitDecimals: true,

        /**
         * @inheritdoc
         */
        constructor: function NumberInputWidgetView(options) {
            NumberInputWidgetView.__super__.constructor.call(this, options);
        },

        findContainer: function() {
            return this.$el;
        },

        initializeWidget: function() {
            this._setPrecision();
            this._setLimitDecimals();
            this._setAttr();
            this.$el.trigger('input');
        },

        disposeWidget: function() {
            this._restoreAttr();
            return NumberInputWidgetView.__super__.disposeWidget.call(this);
        },

        refresh: function() {
            this._setPrecision();
            this._setLimitDecimals();
            this._setAttr();
        },

        _setPrecision: function() {
            const precision = this.$el.data('precision');
            this.precision = _.isUndefined(precision) ? null : precision;
        },

        _setLimitDecimals: function() {
            const limitDecimals = this.$el.data('limit-decimals');
            this.limitDecimals = _.isUndefined(limitDecimals) ? true : limitDecimals;
        },

        _setAttr: function() {
            this._rememberAttr();

            /**
             * Precision could be null in two cases:
             * 1. Field is supposed to be integer, so we should not change number type to text in this case
             * 2. Precision was not set, yet in this case we should not change input type before
             * the correct precision will be set to not trigger: _normalizeNumberFieldValue
             */
            if (this.precision === null) {
                return;
            }

            const initialAttr = {
                type: this.$el.attr('type'),
                step: this.$el.attr('step'),
                min: this.$el.attr('min'),
                max: this.$el.attr('max')
            };

            this.$el.attr({
                type: 'text',
                pattern: this.precision === 0 ? '[0-9]*' : '',
                step: null,
                min: null,
                max: null
            });

            /**
             * Format value to localized value
             * It could be reproduced fo German locale.
             * Example: value 1,23 will be transformed to valid float 1.23 but this float is not localized
             * So the goal of the next code is to keep value localized to avoid further problems
             */
            let value = this.$el.val();

            if (value !== '') {
                // Convert localized value to number
                if (initialAttr.type === 'text') {
                    value = NumberFormatter.unformatStrict(value);
                }
                const localizedFloat = NumberFormatter.formatDecimal(value, {
                    min_fraction_digits: 0,
                    max_fraction_digits: this.precision
                });
                this.$el.val(localizedFloat);
            }
        },

        _rememberAttr: function() {
            this.pattern = this.$el.attr('pattern');
            this.type = this.$el.attr('type');
        },

        _restoreAttr: function() {
            // Do not restore number type of input if it contains decimal
            if (this.type !== 'number' || !this.isDecimalValue()) {
                this.$el.attr('type', this.type);
            }

            if (this.pattern) {
                this.$el.attr('pattern', this.pattern);
            } else {
                this.$el.removeAttr('pattern');
            }
        },

        _useFilter: function() {
            return this.$el.attr('type') !== 'number';
        },

        _addFraction: function(event) {
            if (!this._useFilter()) {
                return;
            }

            const field = this.el;
            const originalValue = field.value;

            // set fixed length start
            const keyName = event.key || String.fromCharCode(event.which);
            if (this.precision > 0 && decimalSeparator === keyName &&
                field.value.length && field.selectionStart === field.value.length) {
                field.value = parseInt(field.value).toFixed(this.precision);

                if (decimalSeparator !== '.') {
                    field.value = field.value.replace('.', decimalSeparator);
                }

                if (!_.isUndefined(field.selectionStart)) {
                    field.selectionEnd = field.value.length;
                    field.selectionStart = field.value.length - this.precision;
                }

                this._triggerEventOnValueChange(event, originalValue);

                event.stopPropagation();
                event.preventDefault();
                return false;
            }
            // set fixed length end
        },

        _normalizeNumberFieldValue: function(event) {
            if (!this._useFilter()) {
                return;
            }

            const field = this.el;
            const originalValue = field.value;
            if (_.isUndefined(field.value)) {
                return;
            }

            // Prevent multi zero value
            if (this.allowZero) {
                field.value = field.value.replace(/^([+-]?)0(\d)*/g, '$10');
            }

            // filter value start
            if (this.precision === 0 && !this.allowZero) {
                field.value = field.value.replace(/^0*/g, '');
            }

            // clear not allowed symbols
            field.value = field.value.replace(new RegExp('(?!\\' + decimalSeparator + '|\\+|-)[?:\\D]', 'g'), '');

            if (field.value[0] === decimalSeparator && this.precision > 0) {
                field.value = '0' + field.value;
            }
            // filter value end

            // validate value start
            let regExpString = '^[+-]?([0-9]*\\' + groupingSeparator + '?)+\\' + groupingSeparator + '?';
            if (this.precision > 0) {
                if (this.limitDecimals) {
                    regExpString += '(\\' + decimalSeparator + '{1})?([0-9]{1,' + this.precision + '})?';
                } else {
                    regExpString += '(\\' + decimalSeparator + '{1})?([0-9]*)?';
                }
            }

            const regExp = new RegExp(regExpString, 'g');
            const substitution = field.value.replace(regExp, '');

            if (!regExp.test(field.value) || substitution.length > 0) {
                field.value = field.value.match(regExp).join('');

                this._triggerEventOnValueChange(event, originalValue);
                event.preventDefault();
                return false;
            } else {
                this._triggerEventOnValueChange(event, originalValue);
            }
            // validate value end
        },

        _triggerEventOnValueChange: function(event, value) {
            const field = event.target;
            if (field.value !== value) {
                $(field).trigger(event);
                $(field).trigger('number-widget:' + event.type);
            }
        },

        isDecimalValue: function() {
            const value = this.$el.val();

            return (value !== undefined && value.indexOf(decimalSeparator) !== -1);
        }
    });
    return NumberInputWidgetView;
});
