define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    return {
        load: function(segment) {
            segment.configureFilters = _.wrap(segment.configureFilters, function(original) {
                var $criteria = $(this.options.filters.criteriaList);

                var aggregatedFieldCondition = $criteria.find('[data-criteria=aggregated-condition-item]');
                if (!_.isEmpty(aggregatedFieldCondition)) {
                    var $itemContainer = $(this.options.column.itemContainer);
                    var columnsCollection = $itemContainer.data('oroui-itemsManagerTable').options.collection;

                    $.extend(true, aggregatedFieldCondition.data('options'), {
                        fieldChoice: this.options.fieldChoiceOptions,
                        filters: this.options.metadata.filters,
                        hierarchy: this.options.metadata.hierarchy,
                        columnsCollection: columnsCollection
                    });
                }

                original.apply(this, _.rest(arguments));
            });
        }
    };
});
