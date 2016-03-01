define(function(require) {
    'use strict';

    var CalendarEventGuestCollection;
    var RoutingCollection = require('oroui/js/app/models/base/routing-collection');

    CalendarEventGuestCollection = RoutingCollection.extend({
        routeDefaults: {
            routeName: 'oro_api_get_calendarevents_guests',
            routeQueryParameterNames: ['page', 'limit']
        },

        stateDefaults: {
            page: 1
        },

        parse: function(response) {
            return response;
        },

        setPage: function(page) {
            this._route.set('page', page);
        }
    });

    return CalendarEventGuestCollection;
});
