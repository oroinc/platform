/*global define*/
define([
    './app/application',
    './app/routes',
    'routing' // @TODO get rid of routes dependency
], function (Application, routes, routing) {
    'use strict';

    var app = new Application({
        // preserves url path as it is (does not add/remove trailing slash)
        trailing: null,
        // defines base url
        root: routing.getBaseUrl(),
        routes: function (match) {
            var i;
            for (i = 0; i < routes.length; i += 1) {
                match(routes[i][0], routes[i][1]);
            }
        },
        controllerSuffix: '-controller',
        // define template for page title
        titleTemplate: function (data) {
            return data.subtitle || '';
        }
    });

    return app;
});
