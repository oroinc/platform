define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    return {
        load: function(segmentComponent) {
            segmentComponent.configureFilters = _.compose(segmentComponent.configureFilters, function() {
                if (!this.conditionBuilderComponent) {
                    return;
                }
                var $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('aggregated-condition-item');
                if ($condition.length) {
                    var $columnsTable = $(this.options.column.itemContainer);

                    $condition.data('options').columnsCollection =
                        $columnsTable.data('oroui-itemsManagerTable').options.collection;
                }
            });
        }
    };
});
