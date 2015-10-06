/** @lends AutocompleteApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Provides access to search API for autocompletes.
     * This class is designed to create from server configuration.
     *
     * @class
     * @augment [ApiAccessor](../api-accessor.md)
     * @exports SearchApiAccessor
     */
    var SearchApiAccessor;

    var _ = require('underscore');
    var ApiAccessor = require('./api-accessor');

    SearchApiAccessor = ApiAccessor.extend(/** @exports SearchApiAccessor.prototype */{
        DEFAULT_HTTP_METHOD: 'GET',

        /**
         * @constructor
         * @param options {object}
         * @param options.search_handler_name {string} NAme of search handler to use
         * @param options.label_field_name {string} Name of the property that will be used as label
         * @param options.id_field_name {string} Optional. Name of the property that will be used as identifier.
         *                                       By default = 'id'
         */
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
            this.idFieldName = options.id_field_name || 'id';
            this.labelFieldName = options.label_field_name;
            SearchApiAccessor.__super__.initialize.call(this, options);
        },

        /**
         * Prepares url parameters before build url
         *
         * @param urlParameters
         * @returns {object}
         */
        prepareUrlParameters: function(urlParameters) {
            urlParameters.query = urlParameters.term;
            return urlParameters;
        },

        /**
         * @inheritDoc
         */
        getUrl: function(urlParameters) {
            urlParameters.name = this.searchHandlerName;
            return this.route.getUrl(this.prepareUrlParameters(urlParameters));
        },

        /**
         * @inheritDoc
         */
        send: function(urlParameters, body, headers) {
            var promise = SearchApiAccessor.__super__.send.apply(this, arguments);
            var resultPromise = promise.then(_.bind(this.formatResult, this));
            // allow to abort request
            resultPromise.abort = _.bind(promise.abort, promise);
            return resultPromise;
        },

        /**
         * Formats response before it will be sent out from this api accessor.
         * Converts it to form
         * {
         *     results: [{id: '<id>', label: '<label>'}, ...],
         *     more: '<more>'
         * }
         *
         * @param response {object}
         * @returns {object}
         */
        formatResult: function(response) {
            var results = response.results;
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                result.id = result[this.idFieldName];
                result.label = result[this.labelFieldName];
            }
            return response;
        }
    });

    return SearchApiAccessor;
});
