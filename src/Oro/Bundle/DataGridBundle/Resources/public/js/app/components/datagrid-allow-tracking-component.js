define(function(require) {
    'use strict';

    var DataGridAllowTrackingComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var GridTagBuilder = require('orosync/js/content/grid-builder');
    var _ = require('underscore');

    DataGridAllowTrackingComponent = BaseComponent.extend({
        options: {
            gridName: null
        },

        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            GridTagBuilder.allowTracking(this.options.gridName);

            DataGridAllowTrackingComponent.__super__.initialize.apply(this, arguments);
        }
    });

    return DataGridAllowTrackingComponent;
});
