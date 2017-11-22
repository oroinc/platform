define(function(require) {
    'use strict';

    var NumberInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var localeSettings = require('orolocale/js/locale-settings');
    var _ = require('underscore');
    var $ = require('jquery');

    var decimalSeparator = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;

    NumberInputWidget = AbstractInputWidget.extend({
        events: {
            'input': '_normalizeNumberFieldValue',
            'change': '_normalizeNumberFieldValue',
            'keypress': '_addFraction'
        },

        initializeWidget: function() {
            this._convertField();
            this.$el.trigger('input');
        },

        findContainer: function() {
            return this.$el;
        },

        disposeWidget: function() {
            this.$el
                .attr('type', 'number')
                .removeAttr('pattern');

            return NumberInputWidget.__super__.disposeWidget.apply(this, arguments);
        },

        _getDataPrecision: function() {
            var precision = this.$el.data('precision');

            return _.isUndefined(precision) ? null : precision;
        },

        _convertField: function() {
            if (_.isDesktop()) {
                this.$el.attr('type', 'text');
            } else {
                this.$el.attr('pattern', this._getDataPrecision() === 0 ? '[0-9]*' : '');
            }
        },

        _addFraction: function(event) {
            var field = event.target;
            var originalValue = field.value;
            var precision = this._getDataPrecision();

            //set fixed length start
            var keyName = event.key || String.fromCharCode(event.which);
            if (precision > 0 && decimalSeparator === keyName &&
                field.value.length && field.selectionStart === field.value.length) {
                field.value = parseInt(field.value).toFixed(precision);

                if (decimalSeparator !== '.') {
                    field.value = field.value.replace('.', decimalSeparator);
                }

                if (!_.isUndefined(field.selectionStart)) {
                    field.selectionEnd = field.value.length;
                    field.selectionStart = field.value.length - precision;
                }

                this._triggerEventOnValueChange(event, originalValue);

                event.stopPropagation();
                event.preventDefault();
                return false;
            }
            //set fixed length end
        },

        _normalizeNumberFieldValue: function(event) {
            var field = event.target;
            var originalValue = field.value;
            var precision = this._getDataPrecision();
            if (_.isUndefined(field.value)) {
                return;
            }

            //filter value start
            if (precision === 0) {
                field.value = field.value.replace(/^0*/g, '');
            }

            field.value = field.value.replace(/(?!\.)[?:\D+]/g, '');//clear not allowed symbols

            if (field.value[0] === decimalSeparator && precision > 0) {
                field.value = '0' + field.value;
            }
            //filter value end

            //validate value start
            var regExpString = '^([0-9]*)';
            if (precision > 0) {
                regExpString += '(\\' + decimalSeparator + '{1})?([0-9]{1,' + precision + '})?';
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
            //validate value end
        },

        _triggerEventOnValueChange: function(event, value) {
            var field = event.target;
            if (field.value !== value) {
                $(field).trigger(event);
                return;
            }
        }
    });
    return NumberInputWidget;
});
