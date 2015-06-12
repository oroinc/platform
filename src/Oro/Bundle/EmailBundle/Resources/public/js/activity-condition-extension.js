/*global define, require*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
], function ($, _) {

    var excludedFields = {
        'Oro\\Bundle\\EmailBundle\\Entity\\Email': [
            'importance',
            'internalDate',
            'head',
            'seen',
            'refs',
            'xMessageId',
            'xThreadId',
        ],
    };

    return {
        load: function (activityCondition) {
            var originalFilter = activityCondition.options.fieldChoice.dataFilter;
            activityCondition.options.fieldChoice.dataFilter = function (entityName, entityFields) {
                if (originalFilter) {
                    entityFields = originalFilter.apply(this, arguments);
                }

                if (!_.has(excludedFields, entityName)) {
                    return entityFields;
                }

                return _.reject(entityFields, function (field) {
                    return _.contains(excludedFields[entityName], field.name);
                });
            };
        }
    };
});
