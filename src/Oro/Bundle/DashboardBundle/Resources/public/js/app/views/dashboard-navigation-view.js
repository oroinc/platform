define(function(require) {
    'use strict';

    var DashboardNavigationView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var dashboardUtil = require('orodashboard/js/dashboard-util');

    DashboardNavigationView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['gridName']),

        initialize: function() {
            DashboardNavigationView.__super__.initialize.apply(this, arguments);

            mediator.on('datagrid:beforeRemoveRow:' + this.gridName, this.onBeforeRemoveRow);
        },

        onBeforeRemoveRow: function(model) {
            dashboardUtil.onDashboardRemove(model.get('id'));
        }
    });

    return DashboardNavigationView;
});
