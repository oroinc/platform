define(['jquery', 'oroform/js/optional-validation-handler'], function($, defaultOptionalValidationHandler) {
    'use strict';

    return {
        /**
         * @constructor
         */
        initialize: function(formElement) {
            var self = this;

            /**
             * Avoid of multiple listener called for single change event
             */
            var optionalValidationGroups = formElement.find('[data-validation-optional-group]');
            var rootOptionalValidationGroups = optionalValidationGroups
                .not('[data-validation-optional-group] [data-validation-optional-group]');
            rootOptionalValidationGroups.on(
                'change',
                'input, select',
                function() {
                    $(this).trigger('validation-optional-group-value-changed');
                }
            );

            /**
             * Custom event used to not interrupt default change event
             */
            optionalValidationGroups
                .on(
                    'validation-optional-group-value-changed',
                    function(event) {
                        var shouldBeBubbled = self.handleFormChanges($(this), $(event.target));
                        if (!shouldBeBubbled) {
                            event.stopPropagation();
                        }
                    }
                );

            rootOptionalValidationGroups.each(function(index, group) {
                self.handleOptionalGroupValidationInitialize($(group));
            });
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
            var self = this;
            $group.children().each(function(index, child) {
                self.handleOptionalGroupValidationInitialize($(child));
            });

            if ($group.data('validation-optional-group') !== undefined) {
                var optionalValidationHandler = this.getHandler($group);
                optionalValidationHandler.initialize($group);
            }
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
