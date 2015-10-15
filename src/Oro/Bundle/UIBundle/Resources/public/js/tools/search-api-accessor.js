/** @lends SearchApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Provides access to the search API for autocompletes.
     * This class is by design to be initiated from server configuration.
     *
     * @class
     * @param {Object} options - Options container Also check the options for [ApiAccessor](./api-accessor.md)
     * @param {string} options.search_handler_name - Name of the search handler to use
     * @param {string} options.label_field_name - Name of the property that will be used as a label
     * @param {string} options.value_field_name - Optional. Name of the property that will be used as an identifier.
     *                                       By default = `'id'`
     * @augments [ApiAccessor](./api-accessor.md)
     * @exports SearchApiAccessor
     */
    var SearchApiAccessor;

    var _ = require('underscore');
    var ApiAccessor = require('./api-accessor');

    SearchApiAccessor = ApiAccessor.extend(/** @exports SearchApiAccessor.prototype */{
        DEFAULT_HTTP_METHOD: 'GET',

        initialize: function(options) {
            if (!options) {
                options = {};
            }
            if (!options.search_handler_name || !options.label_field_name) {
                throw new Error('`search_handler_name` and `label_field_name` options are required');
            }
            if (!options.route) {
                options.route = 'oro_form_autocomplete_search';
            }
            if (!options.query_parameter_names) {
                options.query_parameter_names = [];
            }
            options.query_parameter_names.push.apply(options.query_parameter_names,
                ['page', 'per_page', 'name', 'query']);
            options.query_parameter_names = _.uniq(options.query_parameter_names);
            this.searchHandlerName = options.search_handler_name;
            this.valueFieldName = options.value_field_name || 'id';
            this.labelFieldName = options.label_field_name;
            SearchApiAccessor.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        prepareUrlParameters: function(urlParameters) {
            SearchApiAccessor.__super__.prepareUrlParameters.call(this, urlParameters);
            urlParameters.name = this.searchHandlerName;
            urlParameters.query = urlParameters.term;
            return urlParameters;
        },

        /**
         * Formats response before it is sent out from this api accessor.
         * Converts it to form
         * ``` javascipt
         * {
         *     results: [{id: '<value>', label: '<label>'}, ...],
         *     more: '<more>'
         * }
         * ```
         *
         * @param {Object} response
         * @returns {Object}
         */
        formatResult: function(response) {
            var results = response.results;
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                result.id = result[this.valueFieldName];
                result.label = result[this.labelFieldName];
            }
            return response;
        }
    });

    return SearchApiAccessor;
});
