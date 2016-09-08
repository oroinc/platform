define(['jquery', 'oroform/js/optional-validation-handler'], function($, defaultOptionalValidationHandler) {
    'use strict';

    return {
        /**
         * @constructor
         */
        initialize: function(formElement) {
            var groups = formElement.find('[data-validation-optional-group]');
            groups.each(function(key, group) {
                var $group = $(group);
                var handlerId = $group.data('validation-optional-group-handler');
                if (handlerId) {
                    var handler = require(handlerId);
                    handler.initialize($group);
                } else {
                    defaultOptionalValidationHandler.initialize($group);
                }
            });
        }
    };
});
