define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var NotBlank = {
        message: 'This value should not be blank.',
        payload: null
    };

    var getField = function(name) {
        return $('[name*="[' + name + ']"]');
    };

    var updateValidationData = function(fields, params) {
        _.each(fields, function($field, name) {
            var validationData = $field.data('validation') || {};

            if (!params) {
                delete validationData.NotBlank;
            } else {
                validationData.NotBlank = _.defaults({
                    message: params[name + 'Message']
                }, NotBlank);
            }

            $field.data('validation', validationData).valid();
        });
    };

    var validate = function(fields, params) {
        if ((fields.firstName.val() && fields.lastName.val()) || fields.organization.val()) {
            return true;
        } else {
            updateValidationData(fields, params);
        }
    };

    var resetValidate = function(fields, params) {
        updateValidationData(fields);
        validate(fields, params);
    };

    return [
        'Oro\\Bundle\\AddressBundle\\Validator\\Constraints\\NameOrOrganization',
        function(value, element, params) {
            var event = 'change.NameOrOrganization';
            var fields = {
                firstName: getField('firstName'),
                lastName: getField('lastName'),
                organization: getField('organization')
            };

            _.each(fields, function($field) {
                $field.off(event).on(event, function() {
                    resetValidate(fields, params);
                });
            });

            resetValidate(fields, params);

            return true;
        }
    ];
});
