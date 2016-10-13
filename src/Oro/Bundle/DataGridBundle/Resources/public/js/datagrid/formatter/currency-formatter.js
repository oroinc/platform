define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var NumberFormatter = require('./number-formatter');

    var CurrencyFormatter = function(options) {
        NumberFormatter.call(this, options);
    };
    CurrencyFormatter.prototype = Object.create(NumberFormatter);

    _.extend(CurrencyFormatter.prototype, {
        /** @property {String} */
        style: 'currency',

        /**
         * @inheritDoc
         */
        fromRaw: function(rawData) {
            if (rawData === void 0 || rawData === null || rawData === '') {
                return '';
            }
            var value = Number(rawData.substring(3));
            var currency = rawData.substring(0, 3);
            if (isNaN(value)) {
                return __('oro.datagrid.not_number');
            }
            return this.formatter.call(this, value, currency);
        }
    });

    return CurrencyFormatter;
});
