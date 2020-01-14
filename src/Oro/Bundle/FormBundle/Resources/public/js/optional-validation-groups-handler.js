define(function(require) {
    'use strict';

    const $ = require('jquery');
    const moduleRegistry = require('oroui/js/app/services/module-registry');
    const defaultOptionalValidationHandler = require('oroform/js/optional-validation-handler');

    return {
        /**
         * @constructor
         */
        initialize: function(formElement) {
            const self = this;

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
                    const shouldBeBubbled = self.handleFormChanges($(this), $(event.target));
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
            const optionalValidationHandler = this.getHandler($group);

            return optionalValidationHandler.handle($group, $element);
        },

        /**
         * @param {jQuery} $formElement
         *
         * @return {boolean}
         */
        initializeOptionalValidationGroupHandlers: function($formElement) {
            const self = this;

            const rootOptionalValidationGroups = this.getRootLevelOptionalValidationGroups($formElement);
            rootOptionalValidationGroups.each(function(index, group) {
                const $group = $(group);
                self.initializeOptionalValidationGroupHandlers($group);

                const optionalValidationHandler = self.getHandler($group);
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
            const handler = $group.data('validation-optional-group-handler');

            return handler ? moduleRegistry.get(handler) : defaultOptionalValidationHandler;
        }
    };
});
