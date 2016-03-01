define([
    'underscore',
    './app/application',
    './app/routes',
    'module',
    './extend/polyfill'
].concat(requirejs.s.contexts._.config.appmodules), function(_, Application, routes, module) {
    'use strict';

    var options = _.extend(module.config(), {
        // load routers
        routes: function(match) {
            var i;
            for (i = 0; i < routes.length; i += 1) {
                match(routes[i][0], routes[i][1]);
            }
        },
        // define template for page title
        titleTemplate: function(data) {
            return data.subtitle || '';
        }
    });

    var app = new Application(options);

    return app;
});
