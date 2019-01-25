define(function(require) {
    'use strict';

    var _ = require('underscore');
    var module = require('module');
    var routing = require('routing');

    try {
        var routes = JSON.parse(require('text!oro/routes'));
    } catch (e) {
        throw new Error('Failed to load JS routes.');
    }

    var config = _.extend({debug: false, data: {}}, module.config());
    if (!config.debug) {
        // processed correctly only in case when routing comes via controller
        routes = _.extend(routes, config.data);
    }

    routing.setRoutingData(routes);

    return routing;
});
