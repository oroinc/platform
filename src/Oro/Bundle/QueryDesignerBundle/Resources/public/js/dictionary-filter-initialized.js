/**
 * Resolves filter options
 *
 * @param {Object} filterOptions - object with options which will be enhanced
 * @param {FieldSignature} fieldSignature - information about field that filter will be applied to
 */
export default (filterOptions, fieldSignature) => {
    filterOptions.filterParams = {
        'class': fieldSignature.relatedEntityName
    };
};
