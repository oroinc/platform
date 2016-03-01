define(function(require) {
    'use strict';

    var AutocompleteResultsCollection;
    var RoutingCollection = require('oroui/js/app/models/base/routing-collection');

    AutocompleteResultsCollection = RoutingCollection.extend({
        routeDefaults: {
            routeName: 'oro_form_autocomplete_search',
            routeQueryParameterNames: ['page', 'per_page', 'name', 'query', 'search_by_id']
        },

        stateDefaults: {
            page: 1,
            per_page: 10
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
