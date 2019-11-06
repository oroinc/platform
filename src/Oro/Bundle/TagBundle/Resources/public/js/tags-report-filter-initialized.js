define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');

    /**
     * Resolves filter options
     *
     * @param {object} filterOptions - object with options which will be enhanced
     * @param {object} context - information about context where filter will be applied to
     *
     * @return {jQuery.Deferred} promise
     */
    return function(filterOptions, context) {
        const className = _.last(context).field.relatedEntityName;
        const entityClass = _.last(context).field.entity.className;
        filterOptions.filterParams = {'class': className, 'entityClass': entityClass};
        return $.when(filterOptions);
    };
});
