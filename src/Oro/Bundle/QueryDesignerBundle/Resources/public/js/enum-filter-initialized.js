/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'routing', 'oroui/js/messenger', 'oroui/js/tools'
    ], function ($, _, __, routing, messenger, tools) {
    'use strict';

    var methods = {
        loadEnumChoices: function (className) {
            var choices = [];

            $.ajax({
                url: routing.generate('oro_api_get_entity_extend_enum', {entityName: className.replace(/\\/g, '_')}),
                async: false,
                success: function (data) {
                    data = _.sortBy(data, 'priority');
                    choices = _.map(data, function (item) {
                        return {value: item.id, label: item.name};
                    });
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
                }
            });

            return choices;
        }
    };

    return function (filter, fieldData) {
        var choices = methods.loadEnumChoices(_.last(fieldData).field.related_entity_name),
            nullValue = null;

        if (filter.nullValue) {
            nullValue = _.find(filter.choices, function (choice) {
                return choice.value === filter.nullValue;
            });
        }
        if (nullValue) {
            choices.unshift(nullValue);
        }

        filter.choices = choices;
    };
});
