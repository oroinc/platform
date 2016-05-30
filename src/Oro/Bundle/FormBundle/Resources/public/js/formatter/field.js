define([
    'underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/number', 'orolocale/js/formatter/datetime'
], function(_, __, numberFormatter, dateTimeFormatter) {
    'use strict';

    /**
     * @export  oroform/js/formatter/field
     * @name    oroform.formatter.field
     */
    return {
        /**
         * @param {*} val
         * @returns {string}
         */
        bool: function(val) {
            return val ? __('Yes') : __('No');
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        string: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : _.escape(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        text: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : _.escape(val).replace(/\n/g, '<br />\n');
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        html: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : val.replace(/\n/g, '<br />\n');
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        integer: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : numberFormatter.formatInteger(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        decimal: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : numberFormatter.formatDecimal(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        percent: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : numberFormatter.formatPercent(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        currency: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : numberFormatter.formatCurrency(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        date: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : dateTimeFormatter.formatDate(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        time: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : dateTimeFormatter.formatTime(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        dateTime: function(val) {
            return _.isNull(val) || _.isUndefined(val) ? __('N/A') : dateTimeFormatter.formatDateTime(val);
        },

        /**
         * @param {*} val
         * @returns {string}
         */
        color: function(val) {
            return !val ? __('N/A') : '<i class="color hide-text" title="' +
                val + '" style="background-color: ' + val + ';">' + val + '</i>';
        }
    };
});
