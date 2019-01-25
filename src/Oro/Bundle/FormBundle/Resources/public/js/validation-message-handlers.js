define(function(require) {
    'use strict';

    var ValidationMessageHandlers;

    ValidationMessageHandlers = [
        require('oroform/js/app/views/validation-message-handler/select2-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/date-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/time-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/datetime-validation-message-handler-view')
    ];

    return ValidationMessageHandlers;
});
