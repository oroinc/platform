define(function(require) {
    'use strict';

    const _ = require('underscore');

    return {
        unformatMultiCurrency: function(value) {
            if (!_.isString(value) || value.length < 4) {
                return {
                    amount: null,
                    currency: ''
                };
            }
            const amount = Number(value.substring(3));
            const currency = value.substring(0, 3);
            return {
                amount: amount,
                currency: currency
            };
        }
    };
});
