define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    return {
        load: function(segment) {
            var originalConfigureFilters = segment.configureFilters;
            segment.configureFilters = function() {
                var $condition = this.conditionBuilderComponent.view.getCriteriaOrigin('aggregated-condition-item');
                if ($condition.length) {
                    var $itemContainer = $(this.options.column.itemContainer);
                    $.extend($condition.data('options'), {
                        columnsCollection: $itemContainer.data('oroui-itemsManagerTable').options.collection
                    });
                }
                originalConfigureFilters.call(this);
            };
        }
    };
});
