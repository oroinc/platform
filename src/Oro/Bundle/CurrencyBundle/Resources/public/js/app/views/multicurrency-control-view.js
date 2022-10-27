define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const localeSettings = require('orolocale/js/locale-settings');
    const formatter = require('orolocale/js/formatter/number');
    const BaseView = require('oroui/js/app/views/base/view');

    const MultiCurrencyControlView = BaseView.extend({
        /**
         * @property {Object} keys are ISO3 of currencies and values are multipliers to use in convertation
         */
        rates: null,
        baseField: null,

        events: {
            'change .value-field': 'render',
            'keyup .value-field': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function MultiCurrencyControlView(options) {
            MultiCurrencyControlView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         */
        initialize: function(options) {
            MultiCurrencyControlView.__super__.initialize.call(this, options);
            _.extend(this, _.pick(options, 'rates'));
            this.baseFieldValue = this.$('[name$="[baseCurrencyValue]"]').val();
        },

        render: function() {
            const baseField = this.$('[name$="[baseCurrencyValue]"]');
            const baseFieldContainer = this.$('.base-currency-field');
            const error = $('.alert-error');
            if (!this.baseFieldValue && !error.length) {
                baseFieldContainer.hide();
                this.renderLabel();
            } else {
                this.renderBaseField(baseField, baseFieldContainer);
            }
        },

        renderBaseField: function(baseField, baseFieldContainer) {
            if (this.baseFieldValue) {
                baseField.val(formatter.formatMonetary(this.baseFieldValue));
                const currency = this.$('[name$="[currency]"]').val();
                if (currency === localeSettings.getCurrency()) {
                    baseFieldContainer.hide().val(0);
                } else {
                    baseFieldContainer.show();
                }
            }
        },

        renderLabel: function() {
            if (!_.isEmpty(this.rates)) {
                let rate;
                let value = this._toNumber(this.$('[name$="[value]"]').val());
                const currency = this.$('[name$="[currency]"]').val();
                const $equivalent = this.$('[data-name="default-currency-equivalent"]');
                if (currency === localeSettings.getCurrency() || isNaN(value) || value === 0) {
                    $equivalent.hide().text('');
                } else {
                    rate = _.result(this.rates, currency) || 1;
                    value = formatter.formatCurrency(value * rate);
                    $equivalent.show().text(value);
                }
            }
        },

        _toNumber: function(value) {
            const numberFormats = localeSettings.getNumberFormats('decimal');
            value = String(value).split(numberFormats.grouping_separator_symbol).join('');
            value = value.replace(numberFormats.decimal_separator_symbol, '.');
            return Number(value);
        }
    });

    return MultiCurrencyControlView;
});
