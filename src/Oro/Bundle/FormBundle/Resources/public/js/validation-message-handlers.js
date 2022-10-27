define(function(require) {
    'use strict';

    const ValidationMessageHandlers = [
        require('oroform/js/app/views/validation-message-handler/select2-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/date-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/time-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/datetime-validation-message-handler-view'),
        require('oroform/js/app/views/validation-message-handler/input-validation-message-handler-view')
    ];

    return ValidationMessageHandlers;
});
