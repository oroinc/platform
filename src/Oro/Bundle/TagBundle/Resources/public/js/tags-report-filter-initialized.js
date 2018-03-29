define(function() {
    'use strict';

    /**
     * Resolves filter options
     *
     * @param {Object} filterOptions - object with options which will be enhanced
     * @param {FieldSignature} fieldSignature - information about field that filter will be applied to
     */
    return function(filterOptions, fieldSignature) {
        filterOptions.filterParams = {
            'class': fieldSignature.relatedEntityName,
            'entityClass': fieldSignature.entity
        };
    };
});
