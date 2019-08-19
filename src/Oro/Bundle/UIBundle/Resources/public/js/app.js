define(function(require, exports, module) {
    'use strict';

    var _ = require('underscore');
    var Application = require('oroui/js/app/application');
    var routes = require('oroui/js/app/routes');
    require('oroui/js/extend/polyfill');
    require('app-modules!');

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

    return new Application(options);
});
