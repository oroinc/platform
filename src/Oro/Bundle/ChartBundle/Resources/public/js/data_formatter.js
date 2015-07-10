define([
    'moment', 'orolocale/js/formatter/number', 'orolocale/js/formatter/datetime'
], function(moment, numberFormatter, dateTimeFormatter) {
        'use strict';

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
                var date;
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
                    case 'percent':
                        return numberFormatter.formatPercent(data);
                    case 'day':
                        date = new Date();
                        date.setTime(data);
                        return dateTimeFormatter.getMomentForBackendDate(date).format('MMM DD');
                    case 'time':
                        return moment(data).format(dateTimeFormatter.getTimeFormat());
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
                    case 'date':
                    case 'year':
                    case 'month':
                    case 'day':
                        return Date.parse(data); //add convert to date
                    case 'datetime':
                    case 'time':
                        var date = dateTimeFormatter.unformatBackendDateTime(data);
                        return date;
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
