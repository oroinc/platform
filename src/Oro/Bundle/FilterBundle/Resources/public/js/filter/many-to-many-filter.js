define([
    './choice-filter'
], function(ChoiceFilter) {
    'use strict';

    /**
     * @export  oro/filter/many-to-many-filter
     * @class   oro.filter.ManyToManyFilter
     * @extends oro.filter.ChoiceFilter
     */
    return ChoiceFilter.extend({

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        templateSelector: '#many-to-many-filter-template'
    });
});
