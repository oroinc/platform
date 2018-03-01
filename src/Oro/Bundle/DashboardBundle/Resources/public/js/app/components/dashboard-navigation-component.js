define(function(require) {
    'use strict';

    var DashboardNavigationComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var dashboardUtil = require('orodashboard/js/dashboard-util');

    DashboardNavigationComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

        /**
         * @inheritDoc
         */
        constructor: function DashboardNavigationComponent() {
            DashboardNavigationComponent.__super__.constructor.apply(this, arguments);
        },

        listen: function() {
            var listenTo = {};
            listenTo['datagrid:beforeRemoveRow:' + this.gridName + ' mediator'] = 'onBeforeRemoveRow';
            return listenTo;
        },

        onBeforeRemoveRow: function(model) {
            dashboardUtil.onDashboardRemove(model.get('id'));
        }
    });

    return DashboardNavigationComponent;
});
