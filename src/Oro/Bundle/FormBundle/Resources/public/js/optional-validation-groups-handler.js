define(function(require) {
    'use strict';

    var $ = require('jquery');
    var defaultOptionalValidationHandler = require('oroform/js/optional-validation-handler');

    return {
        /**
         * @constructor
         */
        initialize: function(formElement) {
            var self = this;

            formElement.on(
                'change',
                'input, select, textarea',
                function() {
                    $(this).trigger('validation-optional-group-value-changed');
                }
            );

            /**
             * Custom event used to not interrupt default change event
             */
            formElement.on(
                'validation-optional-group-value-changed',
                '[data-validation-optional-group]',
                function(event) {
                    var shouldBeBubbled = self.handleFormChanges($(this), $(event.target));
                    if (!shouldBeBubbled) {
                        event.stopPropagation();
                    }
                }
            );

            self.initializeOptionalValidationGroupHandlers(formElement);
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
         * @param {jQuery} $formElement
         *
         * @return {boolean}
         */
        initializeOptionalValidationGroupHandlers: function($formElement) {
            var self = this;

            var rootOptionalValidationGroups = this.getRootLevelOptionalValidationGroups($formElement);
            rootOptionalValidationGroups.each(function(index, group) {
                var $group = $(group);
                self.initializeOptionalValidationGroupHandlers($group);

                var optionalValidationHandler = self.getHandler($group);
                optionalValidationHandler.initialize($group);
            });
        },

        /**
         * @param {jQuery} $element
         *
         * @return {boolean}
         */
        getRootLevelOptionalValidationGroups: function($element) {
            return $element.find('[data-validation-optional-group]')
                .not('[data-validation-optional-group] [data-validation-optional-group]');
        },

        /**
         * @param {jQuery} $group Optional validation elements group
         *
         * @return {OptionalValidationHandler}
         */
        getHandler: function($group) {
            /**
             * Handlers should be preloaded using app-module
             */
            var handler = $group.data('validation-optional-group-handler');

            return handler ? require(handler) : defaultOptionalValidationHandler;
        }
    };
});
