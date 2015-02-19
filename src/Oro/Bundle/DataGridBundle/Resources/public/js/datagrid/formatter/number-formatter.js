/*global define*/
define(['underscore', './cell-formatter', 'orolocale/js/formatter/number'
    ], function (_, CellFormatter, formatter) {
    'use strict';


    function getFormatter(style) {
        var functionName = 'format' + style.charAt(0).toUpperCase() + style.slice(1);
        if (!_.isFunction(formatter[functionName])) {
            throw new Error("Formatter doesn't support '" + style + "' number style");
        }
        return formatter[functionName];
    }

    /**
     * Cell formatter that format percent representation
     *
     * @export  orodatagrid/js/datagrid/formatter/number-formatter
     * @class   orodatagrid.datagrid.formatter.NumberFormatter
     * @extends orodatagrid.datagrid.formatter.CellFormatter
     */
    function NumberFormatter(options) {
        options = options ? _.clone(options) : {};
        _.extend(this, options);
        this.formatter = getFormatter(this.style);
    }

    NumberFormatter.prototype = new CellFormatter();

    _.extend(NumberFormatter.prototype, {
        /** @property {String} */
        style: 'decimal',

        /**
         * @inheritDoc
         */
        fromRaw: function (rawData) {
            var formattedData = '';
            if (rawData !== null && rawData !== '') {
                formattedData = this.formatter.call(this, rawData);
            }
            return formattedData;
        },

        /**
         * @inheritDoc
         */
        toRaw: function (formattedData) {
            var rawData = null;
            if (formattedData !== null && formattedData !== '') {
                rawData = formatter.unformat(formattedData)
            }
            return rawData;
        }
    });

    return NumberFormatter;
});
