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

    var updateValidationData = function(fields, params, silent) {
        _.each(fields, function($field, name) {
            var validationData = $field.data('validation') || {};

            if (!params) {
                delete validationData.NotBlank;
            } else {
                validationData.NotBlank = _.defaults({
                    message: params[name + 'Message']
                }, NotBlank);
            }
            $field.data('validation', validationData);

            if (!silent) {
                $field.valid();
            }
        });
    };

    var validate = function(fields, params, silent) {
        if ((fields.firstName.val() && fields.lastName.val()) || fields.organization.val()) {
            return true;
        } else {
            updateValidationData(fields, params, silent);
        }
    };

    var resetValidate = function(fields, params, silent) {
        updateValidationData(fields, null, silent);
        validate(fields, params, silent);
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
            resetValidate(fields, params, true);

            return true;
        }
    ];
});
