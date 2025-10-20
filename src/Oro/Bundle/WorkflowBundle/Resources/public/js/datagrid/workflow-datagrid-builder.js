import _ from 'underscore';
import PermissionModel from 'orouser/js/models/role/permission-model';
import AccessLevelsCollection from 'orouser/js/models/role/access-levels-collection';
import BaseCollection from 'oroui/js/app/models/base/collection';
import RowView from 'oroworkflow/js/datagrid/workflow-action-permissions-row-view';
import ReadonlyRowView from 'orouser/js/datagrid/action-permissions-readonly-row-view';

const workflowDatagridBuilder = {
    processDatagridOptions: function(deferred, options) {
        const reg = /\\/g;
        options.themeOptions.rowView = options.themeOptions.readonly ? ReadonlyRowView : RowView;
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
            workflowDatagridBuilder.build(grid, options);
            deferred.resolve();
        }).fail(function() {
            deferred.reject();
        });
    },

    build: function(grid, options) {
    }
};

export default workflowDatagridBuilder;
