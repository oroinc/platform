define(['jquery', 'underscore', 'orotranslation/js/translator', 'routing', 'oroui/js/messenger'
    ], function($, _, __, routing, messenger) {
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
        filterOptions.filterParams = {'class': className};
        promise.resolveWith(filterOptions);

        return promise;
    };
});
