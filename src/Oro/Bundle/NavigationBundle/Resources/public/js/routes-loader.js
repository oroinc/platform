define(function(require, exports, module) {
    'use strict';

    let config = require('module-config').default(module.id);
    const routing = require('routing');

    return fetch(config.routesResource)
        .then(function(response) {
            return response.json();
        })
        .then(function(routes) {
            config = Object.assign({debug: false, data: {}}, config);
            if (!config.debug) {
                // processed correctly only in case when routing comes via controller
                Object.assign(routes, config.data);
            }
            routing.setRoutingData(routes);

            return routing;
        })
        .catch(function() {
            throw new Error('Unable to load routes from "' + config.routesResource + '"');
        });
});
