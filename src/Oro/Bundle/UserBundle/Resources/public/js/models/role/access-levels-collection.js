define(function(require) {
    'use strict';

    var AccessLevelsCollection;
    var _ = require('underscore');
    var RoutingCollection = require('oroui/js/app/models/base/routing-collection');

    AccessLevelsCollection = RoutingCollection.extend({
        routeDefaults: {
            routeName: 'oro_security_access_levels'
        },

        parse: function(resp, options) {
            return _.map(_.pairs(resp), function(item) {
                return {access_level: parseInt(item[0], 10), access_level_label: item[1]};
            });
        }
    });

    return AccessLevelsCollection;
});
