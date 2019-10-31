define(function(require) {
    'use strict';

    const ChoiceFilter = require('oro/filter/choice-filter');
    const template = require('tpl-loader!orofilter/templates/filter/many-to-many-filter.html');
    /**
     * @export  oro/filter/many-to-many-filter
     * @class   oro.filter.ManyToManyFilter
     * @extends oro.filter.ChoiceFilter
     */
    const ManyToManyFilter = ChoiceFilter.extend({

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
        constructor: function ManyToManyFilter(options) {
            ManyToManyFilter.__super__.constructor.call(this, options);
        }
    });

    return ManyToManyFilter;
});
