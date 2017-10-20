define(function(require) {
    'use strict';

    var DataGridAllowTrackingComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var GridTagBuilder = require('orosync/js/content/grid-builder');

    DataGridAllowTrackingComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

        initialize: function() {
            GridTagBuilder.allowTracking(this.gridName);

            DataGridAllowTrackingComponent.__super__.initialize.apply(this, arguments);
        }
    });

    return DataGridAllowTrackingComponent;
});
