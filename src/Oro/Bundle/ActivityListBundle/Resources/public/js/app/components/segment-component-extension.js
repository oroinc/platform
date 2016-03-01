define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    return {
        load: function(Segment) {
            var originalConfigureFilters = Segment.configureFilters;
            Segment.configureFilters = function() {
                var $criteria = $(this.options.filters.criteriaList);

                var $activityCondition = $criteria.find('[data-criteria=condition-activity]');
                if (!_.isEmpty($activityCondition)) {
                    $.extend(true, $activityCondition.data('options'), {
                        fieldChoice: this.options.fieldChoiceOptions,
                        filters: this.options.metadata.filters,
                        hierarchy: this.options.metadata.hierarchy
                    });
                }

                return originalConfigureFilters.apply(this, arguments);
            };
        }
    };
});
