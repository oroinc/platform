define(function(require) {
    'use strict';

    var MultiCurrencyControlView;
    var _ = require('underscore');
    var localeSettings = require('orolocale/js/locale-settings');
    var formatter = require('orolocale/js/formatter/number');
    var BaseView = require('oroui/js/app/views/base/view');
    MultiCurrencyControlView = BaseView.extend({

        /**
         * @property {Object} keys are ISO3 of currencies and values are multipliers to use in convertation
         */
        rates: null,

        events: {
            'change': 'render',
            'keyup': 'render'
        },

        /**
         * @constructor
         */
        initialize: function(options) {
            MultiCurrencyControlView.__super__.initialize.apply(this, arguments);
            _.extend(this, _.pick(options, 'rates'));
        },

        render: function() {
            if (!_.isEmpty(this.rates)) {
                var rate;
                var value = this._toNumber(this.$('[name$="[value]"]').val());
                var currency = this.$('[name$="[currency]"]').val();
                var $equivalent = this.$('[data-name="default-currency-equivalent"]');
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
            var numberFormats = localeSettings.getNumberFormats('decimal');
            value = String(value).split(numberFormats.grouping_separator_symbol).join('');
            value = value.replace(numberFormats.decimal_separator_symbol, '.');
            return Number(value);
        }
    });

    return MultiCurrencyControlView;
});
