define(function(require) {
    'use strict';

    const RoutingCollection = require('oroui/js/app/models/base/routing-collection');

    const AutocompleteResultsCollection = RoutingCollection.extend({
        routeDefaults: {
            routeName: 'oro_form_autocomplete_search',
            routeQueryParameterNames: ['page', 'per_page', 'name', 'query', 'search_by_id']
        },

        stateDefaults: {
            page: 1,
            per_page: 10
        },

        /**
         * @inheritdoc
         */
        constructor: function AutocompleteResultsCollection(options) {
            AutocompleteResultsCollection.__super__.constructor.call(this, options);
        },

        parse: function(response) {
            return response.results;
        },

        setQuery: function(query) {
            this._route.set('query', query);
        },

        setPage: function(page) {
            this._route.set('page', page);
        }
    });

    return AutocompleteResultsCollection;
});
