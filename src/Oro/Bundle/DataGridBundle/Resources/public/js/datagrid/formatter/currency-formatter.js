define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const NumberFormatter = require('./number-formatter');

    const CurrencyFormatter = function(options) {
        NumberFormatter.call(this, options);
    };
    CurrencyFormatter.prototype = Object.create(NumberFormatter);

    _.extend(CurrencyFormatter.prototype, {
        /** @property {String} */
        style: 'currency',

        /**
         * @inheritdoc
         */
        fromRaw: function(rawData) {
            if (rawData === void 0 || rawData === null || rawData === '') {
                return '';
            }
            const value = Number(rawData.substring(3));
            const currency = rawData.substring(0, 3);
            if (isNaN(value)) {
                return __('oro.datagrid.not_number');
            }
            return this.formatter(value, currency);
        }
    });

    return CurrencyFormatter;
});
