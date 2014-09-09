/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'routing', 'oroui/js/messenger', 'oroui/js/tools'
    ], function ($, _, __, routing, messenger, tools) {
    'use strict';

    function loadEnumChoices(className, successCallback, errorCallback) {

        $.ajax({
            url: routing.generate('oro_api_get_entity_extend_enum', {entityName: className.replace(/\\/g, '_')}),
            success: function (data) {
                data = _.sortBy(data, 'priority');
                var choices = _.map(data, function (item) {
                    return {value: item.id, label: item.name};
                });

                successCallback(choices);
            },
            error: function (jqXHR) {
                messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                if (errorCallback)
                    errorCallback(jqXHR);
            }
        });
    };

    return function (filter, fieldData) {
        // add promise
        var promise = filter.promise = new jQuery.Deferred();

        var className = _.last(fieldData).field.related_entity_name;

        loadEnumChoices(className, function (choices) {
            var nullValue = null,
                filterParams = {'class': className};

            if (filter.nullValue) {
                filterParams.null_value = filter.nullValue;
                nullValue = _.find(filter.choices, function (choice) {
                    return choice.value === filter.nullValue;
                });
            }
            if (nullValue) {
                choices.unshift(nullValue);
            }

            filter.filterParams = filterParams;
            filter.choices = choices;

            // mark promise as resolved
            promise.resolveWith(filter);
        });
    };
});
