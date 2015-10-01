/** @lends AutocompleteApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Provides access to autocomplete API. This class is designed to create from server configuration.
     *
     * @class
     * @augment ApiAccessor
     * @exports AutocompleteApiAccessor
     */
    var AutocompleteApiAccessor;

    var _ = require('underscore');
    var ApiAccessor = require('./api-accessor');

    AutocompleteApiAccessor = ApiAccessor.extend(/** @exports AutocompleteApiAccessor.prototype */{
        DEFAULT_HTTP_METHOD: 'GET',

        initialize: function(options) {
            if (!options) {
                options = {};
            }
            if (!options.name || !options.entity_name) {
                throw new Error('`name` (service name) and `entity_name` options are required');
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
            this.entity_name = options.entity_name;
            this.name = options.name;
            AutocompleteApiAccessor.__super__.initialize.call(this, options);
        },

        getUrl: function(urlParameters) {
            urlParameters.query = [urlParameters.term, this.entity_name, 'ASSIGN', urlParameters.id, ''].join(';');
            urlParameters.name = this.name;
            return this.route.getUrl(urlParameters);
        }
    });

    return AutocompleteApiAccessor;
});
