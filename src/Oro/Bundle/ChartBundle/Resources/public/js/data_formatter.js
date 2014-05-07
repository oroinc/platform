/*global define*/
define(['orolocale/js/formatter/number', 'orolocale/js/formatter/datetime'],
    function (numberFormatter, dateTimeFormatter) {
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
                    case 'date':
                        var date = new Date();
                        date.setTime(data);
                        return dateTimeFormatter.formatDate(date);
                    case 'datetime':
                        var date = new Date();
                        date.setTime(data);
                        return dateTimeFormatter.formatDateTime(date);
                    case 'money': case 'currency':
                        return numberFormatter.formatCurrency(data);
                    case 'percent':
                        return numberFormatter.formatPercent(data);
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
                       return Date.parse(data); //add convert to date
                   case 'datetime':
                       var date = dateTimeFormatter.unformatBackendDateTime(data);
                       return date.getTime();
                   default:
                       return null;
               }
           }
        };
    }
);
