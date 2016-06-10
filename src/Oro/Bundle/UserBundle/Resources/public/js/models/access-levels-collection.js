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
                return {value: item[0], value_text: item[1]};
            });
        }
    });

    return AccessLevelsCollection;
});
