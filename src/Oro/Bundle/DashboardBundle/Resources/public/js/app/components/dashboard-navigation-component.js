define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const dashboardUtil = require('orodashboard/js/dashboard-util');

    const DashboardNavigationComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

        /**
         * @inheritdoc
         */
        constructor: function DashboardNavigationComponent(options) {
            DashboardNavigationComponent.__super__.constructor.call(this, options);
        },

        listen: function() {
            const listenTo = {};
            listenTo['datagrid:beforeRemoveRow:' + this.gridName + ' mediator'] = 'onBeforeRemoveRow';
            return listenTo;
        },

        onBeforeRemoveRow: function(model) {
            dashboardUtil.onDashboardRemove(model.get('id'));
        }
    });

    return DashboardNavigationComponent;
});
