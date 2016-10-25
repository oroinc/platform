define(function(require) {
    'use strict';

    var _ = require('underscore');

    return {
        unformatMultiCurrency: function(value) {
            if (!_.isString(value) || value.length < 4) {
                return {
                    amount: null,
                    currency: ''
                };
            }
            var amount = Number(value.substring(3));
            var currency = value.substring(0, 3);
            return {
                amount: amount,
                currency: currency
            };
        }
    };
});
