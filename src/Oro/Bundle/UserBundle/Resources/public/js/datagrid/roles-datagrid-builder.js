define(function(require) {
    'use strict';

    var rolesDatagridBuilder;
    var _ = require('underscore');
    var PermissionModel = require('orouser/js/models/permission-model');
    var AccessLevelsCollection = require('orouser/js/models/access-levels-collection');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    rolesDatagridBuilder = {
        processDatagridOptions: function(deferred, options) {
            var reg = /\\/g;
            _.each(options.data.data, function(item) {
                item.permissions = new BaseCollection(item.permissions, {
                    model: PermissionModel
                });
                item.permissions.accessLevels = new AccessLevelsCollection([], {
                    routeParameters: {
                        oid: item.identity.replace(reg, '_')
                    }
                });
            });
            deferred.resolve();
        },

        init: function(deferred, options) {
            deferred.resolve();
        }
    };

    return rolesDatagridBuilder;
});
