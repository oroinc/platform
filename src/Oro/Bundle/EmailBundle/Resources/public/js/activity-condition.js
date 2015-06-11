/*global define, require*/
/*jslint nomen: true*/
define([
    'jquery',
    'underscore',
    'oroactivitylist/js/activity-condition',
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

    $.widget('oroactivity.activityCondition', $.oroactivity.activityCondition, {
        _create: function () {
            var originalFilter = this.options.fieldChoice.dataFilter;
            this.options.fieldChoice.dataFilter = function (entityName, entityFields) {
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

            this._superApply(arguments);
        }
    });
});
