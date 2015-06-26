/*jslint nomen:true*/
/*global define*/
define([
    './choice-filter'
], function (ChoiceFilter) {

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
