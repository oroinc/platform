define(function(require) {
    'use strict';

    var ManyToManyFilter;
    var ChoiceFilter = require('oro/filter/choice-filter');
    var template = require('tpl-loader!orofilter/templates/filter/many-to-many-filter.html');
    /**
     * @export  oro/filter/many-to-many-filter
     * @class   oro.filter.ManyToManyFilter
     * @extends oro.filter.ChoiceFilter
     */
    ManyToManyFilter = ChoiceFilter.extend({

        /**
         * Template selector for filter criteria
         *
         * @property
         */
        template: template,
        templateSelector: '#many-to-many-filter-template',

        /**
         * @inheritDoc
         */
        constructor: function ManyToManyFilter() {
            ManyToManyFilter.__super__.constructor.apply(this, arguments);
        }
    });

    return ManyToManyFilter;
});
