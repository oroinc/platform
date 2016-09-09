define(['jquery', 'oroform/js/optional-validation-handler'], function($, defaultOptionalValidationHandler) {
    'use strict';

    return {
        /**
         * @constructor
         */
        initialize: function(formElement) {
            var self = this;

            var optionalValidationGroups = formElement.find('[data-validation-optional-group]');
            optionalValidationGroups.on(
                'change',
                'input, select',
                function() {
                    $(this)
                        .closest('[data-validation-optional-group]')
                        .trigger('validation-optional-group-value-changed', this);
                }
            );

            /**
             * Custom event used to not interrupt default change event
             */
            optionalValidationGroups.on(
                'validation-optional-group-value-changed',
                function(event, targetElement) {
                    var shouldBeBubbled = self.handleFormChanges($(this), $(targetElement));
                    if (!shouldBeBubbled) {
                        event.stopPropagation();
                    }
                }
            ).on(
                'validation-optional-group-initialize',
                function(event) {
                    var shouldBeBubbled = self.handleOptionalGroupValidationInitialize($(this));
                    if (!shouldBeBubbled) {
                        event.stopPropagation();
                    }
                }
            ).trigger('validation-optional-group-initialize');
        },

        /**
         * @param {jQuery} $group   Optional validation elements group
         * @param {jQuery} $element Current Element
         *
         * @return {boolean}
         */
        handleFormChanges: function($group, $element) {
            var optionalValidationHandler = this.getHandler($group);

            return optionalValidationHandler.handle($group, $element);
        },

        /**
         * @param {jQuery} $group Optional validation elements group
         *
         * @return {boolean}
         */
        handleOptionalGroupValidationInitialize: function($group) {
            var optionalValidationHandler = this.getHandler($group);

            return optionalValidationHandler.initialize($group);
        },

        /**
         * @param {jQuery} $group Optional validation elements group
         *
         * @return {OptionalValidationHandler}
         */
        getHandler: function($group) {
            var handler = $group.data('validation-optional-group-handler');

            return handler ? require(handler) : defaultOptionalValidationHandler;
        }
    };
});
