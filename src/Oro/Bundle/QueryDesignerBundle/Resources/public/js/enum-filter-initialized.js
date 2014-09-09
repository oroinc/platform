/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/promise', 'routing', 'oroui/js/messenger', 'oroui/js/tools'
    ], function ($, _, __, Promise, routing, messenger, tools) {
    'use strict';

    function loadEnumChoices(className, success, error) {

        $.ajax({
            url: routing.generate('oro_api_get_entity_extend_enum', {entityName: className.replace(/\\/g, '_')}),
            success: function (data) {
                var choices = [];
                data = _.sortBy(data, 'priority');
                choices = _.map(data, function (item) {
                    return {value: item.id, label: item.name};
                });

                success(choices);
            },
            error: function (jqXHR) {
                var err = jqXHR.responseJSON,
                    msg = __('Sorry, unexpected error was occurred');
                if (tools.debug) {
                    if (err.message) {
                        msg += ': ' + err.message;
                    } else if (err.errors && $.isArray(err.errors)) {
                        msg += ': ' + err.errors.join();
                    } else if ($.type(err) === 'string') {
                        msg += ': ' + err;
                    }
                }
                messenger.notificationFlashMessage('error', msg);

                if (error)
                    error(jqXHR);
            }
        });
    };

    return function (filter, fieldData) {
        // add promise
        var promise = filter.promise = new Promise(filter);

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
            promise.setResolved();
        });
    };
});
