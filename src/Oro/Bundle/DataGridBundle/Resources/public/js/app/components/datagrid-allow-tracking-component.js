define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const GridTagBuilder = require('orosync/js/content/grid-builder');

    const DataGridAllowTrackingComponent = BaseComponent.extend({
        optionNames: BaseComponent.prototype.optionNames.concat(['gridName']),

        /**
         * @inheritdoc
         */
        constructor: function DataGridAllowTrackingComponent(options) {
            DataGridAllowTrackingComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            GridTagBuilder.allowTracking(this.gridName);

            DataGridAllowTrackingComponent.__super__.initialize.call(this, options);
        }
    });

    return DataGridAllowTrackingComponent;
});
