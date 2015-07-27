define(['jquery', 'underscore', 'orotranslation/js/translator', 'routing', 'oroui/js/messenger'
    ], function($, _, __, routing, messenger) {
    'use strict';

    function loadEnumChoices(className, successCallback, errorCallback) {
        console.log(2, 'request to oro_api_get_dictionary_values');
        $.ajax({
            url: routing.generate(
                'oro_api_get_dictionary_values',
                {dictionary: className.replace(/\\/g, '_'), limit: -1}
            ),
            success: function(data) {
                data = _.sortBy(data, 'order');
                var choices = _.map(data, function(item) {
                    return {value: item.name, label: item.name};
                });

                successCallback(choices);
            },
            error: function(jqXHR) {
                messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                if (errorCallback) {
                    errorCallback(jqXHR);
                }
            }
        });
    }

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

        loadEnumChoices(className, function(choices) {
            console.log(3, 'loadEnumChoices dictionary-filter-initializer');
            var nullValue = null;
            var filterParams = {'class': className};

            // keep null value option if defined in options
            if (filterOptions.nullValue) {
                filterParams.null_value = filterOptions.nullValue;
                nullValue = _.find(filterOptions.choices, function(choice) {
                    return choice.value === filterOptions.nullValue;
                });
                if (nullValue) {
                    choices.unshift(nullValue);
                }
            }

            filterOptions.filterParams = filterParams;
            filterOptions.choices = choices;

            console.log(4, 'done prepare filter options');

            // mark promise as resolved
            promise.resolveWith(filterOptions);
        });

        return promise;
    };
});
