define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    /**
     * Resolves filter options
     *
     * @param {object} filterOptions - object with options which will be enhanced
     * @param {object} context - information about context where filter will be applied to
     *
     * @return {jQuery.Deferred} promise
     */
    return function(filterOptions, context) {
        var className = _.last(context).field.relatedEntityName;
        filterOptions.filterParams = {'class': className};
        return $.when(filterOptions);
    };
});
