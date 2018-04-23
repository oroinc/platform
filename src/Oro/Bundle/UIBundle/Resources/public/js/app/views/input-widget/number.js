define(function(require) {
    'use strict';

    var NumberInputWidgetView;
    var AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    var localeSettings = require('orolocale/js/locale-settings');
    var _ = require('underscore');
    var $ = require('jquery');

    var decimalSettings = localeSettings.getNumberFormats('decimal');
    var decimalSeparator = decimalSettings.decimal_separator_symbol;
    var groupingSeparator = decimalSettings.grouping_separator_symbol;

    NumberInputWidgetView = AbstractInputWidgetView.extend({
        events: {
            input: '_normalizeNumberFieldValue',
            change: '_normalizeNumberFieldValue',
            keypress: '_addFraction'
        },

        precision: null,

        type: null,

        pattern: null,

        allowZero: true,

        /**
         * @inheritDoc
         */
        constructor: function NumberInputWidgetView() {
            NumberInputWidgetView.__super__.constructor.apply(this, arguments);
        },

        findContainer: function() {
            return this.$el;
        },

        initializeWidget: function() {
            this._setPrecision();
            this._setAttr();
            this.trigger('input');
        },

        disposeWidget: function() {
            this._restoreAttr();
            return NumberInputWidgetView.__super__.disposeWidget.apply(this, arguments);
        },

        _setPrecision: function() {
            var precision = this.$el.data('precision');
            this.precision = _.isUndefined(precision) ? null : precision;
        },

        _setAttr: function() {
            this._rememberAttr();
            this.$el.attr('type', _.isDesktop() && this.precision !== null ? 'text' : 'number')
                .attr('pattern', this.precision === 0 ? '[0-9]*' : '');
        },

        _rememberAttr: function() {
            this.pattern = this.$el.attr('pattern');
            this.type = this.$el.attr('type');
        },

        _restoreAttr: function() {
            this.$el.attr('type', this.type);
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

            var field = this.el;
            var originalValue = field.value;

            // set fixed length start
            var keyName = event.key || String.fromCharCode(event.which);
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

            var field = this.el;
            var originalValue = field.value;
            if (_.isUndefined(field.value)) {
                return;
            }

            // Prevent multi zero value
            if (this.allowZero) {
                field.value = field.value.replace(/^0(\d)*/g, '0');
            }

            // filter value start
            if (this.precision === 0 && !this.allowZero) {
                field.value = field.value.replace(/^0*/g, '');
            }

            // clear not allowed symbols
            field.value = field.value.replace(new RegExp('(?!\\' + decimalSeparator + ')[?:\\D+]', 'g'), '');

            if (field.value[0] === decimalSeparator && this.precision > 0) {
                field.value = '0' + field.value;
            }
            // filter value end

            // validate value start
            var regExpString = '^([0-9]*\\' + groupingSeparator + '?)+\\' + groupingSeparator + '?';
            if (this.precision > 0) {
                regExpString += '(\\' + decimalSeparator + '{1})?([0-9]{1,' + this.precision + '})?';
            }

            var regExp = new RegExp(regExpString, 'g');
            var substitution = field.value.replace(regExp, '');

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
            var field = event.target;
            if (field.value !== value) {
                $(field).trigger(event);
            }
        }
    });
    return NumberInputWidgetView;
});
