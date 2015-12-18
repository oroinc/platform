define(['jquery', 'underscore'
    ], function($, _) {
    'use strict';

    /**
     * Resolves filter options
     *
     * @param {object} filterOptions - object with options which will be enhanced
     * @param {object} context - information about context where filter will be applied to
     *
     * @return {jQuery.Deferred} promise
     */
    return function(filterOptions, context) {
        var promise = new $.Deferred();
        var className = _.last(context).field.related_entity_name;
        var entityClass = _.last(context).field.entity.name;

        filterOptions.filterParams = {'class': className, 'entityClass': entityClass};

        // mark promise as resolved
        promise.resolveWith(filterOptions);

        return promise;
    };
});
