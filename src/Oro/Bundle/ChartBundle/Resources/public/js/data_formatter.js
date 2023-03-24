define(function(require) {
    'use strict';

    const numberFormatter = require('orolocale/js/formatter/number');
    const dateTimeFormatter = require('orolocale/js/formatter/datetime');

    /**
     * @export orochart/js/data_formatter
     * @name   dataFormatter
     */
    return {
        /**
         * @param {string} data
         * @param {string} format
         * @return {*}
         */
        formatValue: function(data, format) {
            let date;
            switch (format) {
                case 'integer':
                case 'smallint':
                case 'bigint':
                    return numberFormatter.formatInteger(data);
                case 'decimal':
                case 'float':
                    return numberFormatter.formatDecimal(data);
                case 'boolean':
                    return numberFormatter.formatInteger(data);
                case 'year':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.getMomentForBackendDate(date).format('YYYY');
                case 'month':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.getMomentForBackendDate(date).format('MMM YYYY');
                case 'date':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.formatDate(date);
                case 'datetime':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.formatDateTime(date);
                case 'money': case 'currency':
                    return numberFormatter.formatCurrency(data);
                case 'currency_rounded':
                    return numberFormatter.formatCurrencyRounded(data);
                case 'percent':
                    return numberFormatter.formatPercent(data);
                case 'day':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.getMomentForBackendDate(date).format('MMM DD');
                case 'time':
                    date = new Date();
                    date.setTime(data);
                    return dateTimeFormatter.getMomentForBackendDate(date).format(dateTimeFormatter.getTimeFormat());
                default:
                    return null;
            }
        },
        /**
         * @param {string} data
         * @param {string} format
         * @return {*}
         */
        parseValue: function(data, format) {
            switch (format) {
                case 'integer':
                case 'smallint':
                case 'bigint':
                case 'boolean':
                    if (data === null) {
                        data = 0;
                    }
                    return parseInt(data);
                case 'decimal':
                case 'float':
                case 'money':
                case 'currency':
                case 'percent':
                    if (data === null) {
                        data = 0;
                    }
                    return parseFloat(data);
                case 'currency_rounded':
                    if (data === null) {
                        data = 0;
                    }
                    return Math.round(data);
                case 'date':
                case 'year':
                case 'month':
                case 'day':
                    return dateTimeFormatter.unformatBackendDateTime(data).valueOf(); // add convert to date
                case 'datetime':
                case 'time':
                    return dateTimeFormatter.unformatBackendDateTime(data);
                default:
                    return null;
            }
        },
        /**
         * @param {string} format
         * @return {boolean}
         */
        isValueNumerical: function(format) {
            switch (format) {
                case 'integer':
                case 'smallint':
                case 'bigint':
                case 'boolean':
                case 'decimal':
                case 'float':
                case 'money':
                case 'currency':
                case 'currency_rounded':
                case 'percent':
                case 'date':
                case 'month':
                case 'datetime':
                    return true;
                default:
                    return false;
            }
        }
    };
}
);
