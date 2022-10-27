define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');

    return {
        load: function(segmentComponent) {
            segmentComponent.configureFilters = _.compose(segmentComponent.configureFilters, function() {
                if (!this.conditionBuilderComponent) {
                    return;
                }
                const $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('aggregated-condition-item');
                if ($condition.length) {
                    const $columnsTable = $(this.options.column.itemContainer);

                    $condition.data('options').columnsCollection =
                        $columnsTable.data('oroui-itemsManagerTable').options.collection;
                }
            });
        }
    };
});
