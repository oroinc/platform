/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'routing', 'oroui/js/messenger'
    ], function ($, _, __, routing, messenger) {
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


    /**
     * Resolves filter options
     *
     * @param {object} filterOptions - object with options which will be enhanced
     * @param {object} context - information about context where filter will be applied to
     *
     * @return {jQuery.Deferred} promise
     */
    return function (filterOptions, context) {
        var promise = new jQuery.Deferred(),
            className = _.last(context).field.related_entity_name;

        loadEnumChoices(className, function (choices) {
            var nullValue = null,
                filterParams = {'class': className};

            // keep null value option if defined in options
            if (filterOptions.nullValue) {
                filterParams.null_value = filterOptions.nullValue;
                nullValue = _.find(filterOptions.choices, function (choice) {
                    return choice.value === filterOptions.nullValue;
                });
                if (nullValue) {
                    choices.unshift(nullValue);
                }
            }

            filterOptions.filterParams = filterParams;
            filterOptions.choices = choices;

            // mark promise as resolved
            promise.resolveWith(filterOptions);
        });

        return promise;
    };
});
