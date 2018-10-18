define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

    var NotBlank = {
        message: 'This value should not be blank.',
        payload: null
    };

    var getField = function(name, element, params) {
        var namePart = '[' + name + ']';

        // Take into account parent's form name to distinguish between two different addresses for the same form
        if (params.parentFormName) {
            namePart = '[' + params.parentFormName + ']' + namePart;
        }

        return $(element).closest('form').find('[name*="' + namePart + '"]');
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
            var event = 'change.NameOrOrganization' + (params.parentFormName ? params.parentFormName : '');
            var fields = {
                firstName: getField('firstName', element, params),
                lastName: getField('lastName', element, params),
                organization: getField('organization', element, params)
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

