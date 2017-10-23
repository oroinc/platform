define(function(require) {
    'use strict';

    var DashboardNavigationComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var dashboardUtil = require('orodashboard/js/dashboard-util');

    DashboardNavigationComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

        listen: function() {
            var listenTo = {};
            listenTo['datagrid:beforeRemoveRow:' + this.gridName + ' mediator'] = 'onBeforeRemoveRow';
            return listenTo;
        },

        initialize: function() {
            DashboardNavigationComponent.__super__.initialize.apply(this, arguments);

            mediator.on('datagrid:beforeRemoveRow:' + this.gridName, this.onBeforeRemoveRow);
        },

        onBeforeRemoveRow: function(model) {
            dashboardUtil.onDashboardRemove(model.get('id'));
        }
    });

    return DashboardNavigationComponent;
});
