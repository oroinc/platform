define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const CellFormatter = require('./cell-formatter');
    const formatter = require('orolocale/js/formatter/number');

    function getFormatter(style) {
        const functionName = 'format' + style.charAt(0).toUpperCase() + style.slice(1);
        if (!_.isFunction(formatter[functionName])) {
            throw new Error('Formatter doesn\'t support "' + style + '" number style');
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
         * @inheritdoc
         */
        fromRaw: function(rawData) {
            if (rawData === void 0 || rawData === null || rawData === '') {
                return '';
            }
            if (isNaN(rawData)) {
                return __('oro.datagrid.not_number');
            }
            return this.formatter(rawData);
        },

        /**
         * @inheritdoc
         */
        toRaw: function(formattedData) {
            let rawData = null;
            if (formattedData !== null && formattedData !== '') {
                rawData = formatter.unformat(formattedData);
            }
            return rawData;
        }
    });

    return NumberFormatter;
});
