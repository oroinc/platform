define(function(require) {
    'use strict';

    var rolesDatagridBuilder;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var PermissionModel = require('orouser/js/models/role/permission-model');
    var AccessLevelsCollection = require('orouser/js/models/role/access-levels-collection');
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
            options.gridPromise.done(function(grid) {
                rolesDatagridBuilder.build(grid, options);
                deferred.resolve();
            }).fail(function() {
                deferred.reject();
            });
        },

        build: function(grid, options) {
            var currentCategory = {
                id: options.currentCategoryId || 'all'
            };
            var filterer = function(model) {
                return currentCategory.id === 'all' || model.get('group') === currentCategory.id;
            };
            grid.body.filter(filterer);
            grid.body.listenTo(mediator, 'role:entity-category:changed', function(category) {
                _.extend(currentCategory, category);
                grid.body.filter();
                grid.$el.toggle(grid.body.visibleItems.length > 0);
            });
        }
    };

    return rolesDatagridBuilder;
});
