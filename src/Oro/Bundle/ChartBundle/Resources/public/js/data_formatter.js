/*global define*/
define(['orolocale/js/formatter/number', 'orolocale/js/formatter/datetime'],
    function (numberFormatter, dateTimeFormatter) {
        'use strict';

        /**
         * @export oro/chart/data_formatter
         * @name   dataFormatter
         */
        return {
           formatLabel: function(data, format) {
                switch (format){
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
                        return dateTimeFormatter.formatDate(data);
                    case 'datetime':
                        return dateTimeFormatter.formatDateTime(data);
                    case 'money':
                        return numberFormatter.formatCurrency(data);
                    case 'percent':
                        return numberFormatter.formatPercent(data);
                    default:
                        return null;
                }
           },
           clearValue: function(data, format){
               switch (format){
                   case 'integer':
                   case 'smallint':
                   case 'bigint':
                   case 'boolean':
                       return parseInt(data);
                   case 'decimal':
                   case 'float':
                   case 'money':
                   case 'percent':
                       return parseFloat(data);
                   case 'date':
                       var date  = new Date(data);
                       return date.getTime(); //add convert to date
                   case 'datetime':
                       return data; //add convert to date time
                   default:
                       return null;
               }
           }
        };
    }
);
