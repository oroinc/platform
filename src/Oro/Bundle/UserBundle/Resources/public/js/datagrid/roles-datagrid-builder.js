import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import PermissionModel from 'orouser/js/models/role/permission-model';
import AccessLevelsCollection from 'orouser/js/models/role/access-levels-collection';
import BaseCollection from 'oroui/js/app/models/base/collection';
import RowView from 'orouser/js/datagrid/action-permissions-row-view';
import ReadonlyRowView from 'orouser/js/datagrid/action-permissions-readonly-row-view';

const rolesDatagridBuilder = {
    processDatagridOptions: function(deferred, options) {
        const reg = /\\/g;
        options.metadata.columns = options.metadata.columns.filter(column => column.renderable);
        if (options.themeOptions.readonly) {
            options.metadata.rowActions = {};
            options.themeOptions.rowView = ReadonlyRowView;
        } else {
            options.themeOptions.rowView = RowView;
        }
        _.each(options.data.data, function(item) {
            item.permissions = new BaseCollection(item.permissions, {
                model: PermissionModel
            });

            const routeParameters = {oid: item.identity.replace(reg, '_'), permission: ''};
            if (options.metadata.options.access_level_route) {
                routeParameters.routeName = options.metadata.options.access_level_route;
            }
            const accessLevelsCollection = new AccessLevelsCollection([], {
                routeParameters: routeParameters
            });
            item.permissions.accessLevels = accessLevelsCollection;
            item.permissions.each(function(model) {
                model.accessLevels = accessLevelsCollection;
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
        const currentCategory = {
            id: options.currentCategoryId || 'all'
        };
        const filterer = function(model) {
            return currentCategory.id === 'all' || model.get('group') === currentCategory.id;
        };
        grid.body.filter(filterer);
        grid.body.listenTo(mediator, 'role:entity-category:changed', function(category) {
            _.extend(currentCategory, category);
            grid.body.filter();
            grid.$el.toggle(grid.body.visibleItems.length > 0);
            grid.trigger('content:update');
        });
    }
};

export default rolesDatagridBuilder;
