define(function(require) {
    'use strict';

    const _ = require('underscore');
    const ApiAccessor = require('oroui/js/tools/api-accessor');

    const CurrencyApiAccessor = ApiAccessor.extend(/** @lends CurrencyApiAccessor.prototype */{
        /**
         * Prepares the request body.
         *
         * @param {Object} body - Map of the url parameters to use
         * @returns {Object}
         */
        formatBody: function(body) {
            let value;
            const formattedBody = {};
            const cellField = this.initialOptions.cell_field;
            const valueField = this.initialOptions.value_field;
            const currencyField = this.initialOptions.currency_field;

            if (cellField in body) {
                value = body[cellField];
                if (value.length > 3) {
                    formattedBody[valueField] = Number(body[cellField].substring(3)).toFixed(4);
                    formattedBody[currencyField] = body[cellField].substring(0, 3);
                } else {
                    formattedBody[valueField] = null;
                    formattedBody[currencyField] = null;
                }
            }
            return formattedBody;
        },

        onAjaxError: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                let cellFieldErrors = [];
                const errors = xhr.responseJSON.errors;
                const cellField = this.initialOptions.cell_field;
                _.each(_.pick(this.initialOptions, 'value_field', 'currency_field'), function(entityField) {
                    const fieldErrors = _.result(errors.children, entityField);
                    if (fieldErrors && _.isArray(fieldErrors.errors)) {
                        cellFieldErrors = cellFieldErrors.concat(fieldErrors.errors);
                        delete errors.children[entityField];
                    }
                });
                if (cellFieldErrors.length !== 0) {
                    if (false === cellField in errors.children) {
                        errors.children[cellField] = {};
                    }
                    if (!_.isArray(errors.children[cellField].errors)) {
                        errors.children[cellField].errors = [];
                    }
                    errors.children[cellField].errors = errors.children[cellField].errors.concat(cellFieldErrors);
                    xhr.responseJSON.errors = errors;
                }
            }
            return xhr;
        }
    });

    return CurrencyApiAccessor;
});
