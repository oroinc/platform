define(['jquery'], function($) {
    'use strict';

    /**
     * @export  oroform/js/optional-validation-handler
     * @class   OptionalValidationHandler
     */
    return {
        /**
         * @param  {jQuery}  $group Optional validation elements group
         *
         * @return {boolean}        Should be bubbled
         */
        initialize: function($group) {
            var self = this;

            var labels = this.getGroupElements($group, $group.find('label[data-required]'));
            labels.addClass('required');

            var labelAsterisk = labels.find('em');
            labelAsterisk.hide().html('*');
            if (self.hasNotEmptyDescendantGroup($group) ||
                self.hasNotEmptyInput($group) ||
                self.hasNotEmptySelect($group)) {
                labelAsterisk.show();

                $group.data('group-validation-required', true);
            }

            return true;
        },

        /**
         * @param {jQuery} $group
         * @returns {boolean}
         */
        hasNotEmptyDescendantGroup: function($group) {
            return $group.find('[data-validation-optional-group][data-group-validation-required]').length > 0;
        },

        /**
         * @param {jQuery} $group
         * @returns {boolean}
         */
        hasNotEmptyInput: function($group) {
            var elementsSelector = 'input[type!="checkbox"][type!="radio"][type!="button"][data-required],' +
                ' input[type="radio"][data-required]:checked,' +
                ' input[type="checkbox"][data-required]:checked';

            var checkedElements = this.getGroupElements($group, $group.find(elementsSelector));
            for (var i = 0; i < checkedElements.length; i++) {
                if (!this.isValueEmpty($(checkedElements[i]).val())) {
                    return true;
                }
            }

            return false;
        },

        /**
         * @param {jQuery} $group
         * @returns {boolean}
         */
        hasNotEmptySelect: function($group) {
            var elements = this.getGroupElements($group, $group.find('select'));
            for (var i = 0; i < elements.length; i++) {
                if (!this.isValueEmpty($(elements[i]).find('option:selected').val())) {
                    return true;
                }
            }

            return false;
        },

        /**
         * @param  {jQuery}  $group   Optional validation elements group
         * @param  {jQuery}  $element Changed Element
         *
         * @return {boolean}          Should be bubbled
         */
        handle: function($group, $element) {
            var tagName = $element.prop('tagName').toLowerCase();

            switch (tagName) {
                case 'select':
                    this.selectHandler($group, $element);
                    break;
                case 'input':
                    this.inputHandler($group, $element);
                    break;
            }

            return true;
        },

        /**
         * @param {jQuery} $group   Optional validation elements group
         * @param {jQuery} $element Changed Element
         */
        inputHandler: function($group, $element) {
            this.handleGroupRequire($group, $element.val());
        },

        /**
         * @param {jQuery} $group   Optional validation elements group
         * @param {jQuery} $element Changed Element
         */
        selectHandler: function($group, $element) {
            this.handleGroupRequire($group, $element.find('option:selected').val());
        },

        /**
         * @param {jQuery}           $group Optional validation elements group
         * @param {string|undefined} value  Changed Element value
         */
        handleGroupRequire: function($group, value) {
            if (this.isValueEmpty(value)) {
                $group.find('label[data-required] em').hide();
                this.clearValidationErrorsAndDisableValidation($group);
                $group.data('group-validation-required', false);
            } else {
                $group.find('label[data-required] em').show();
                var inputs = this.getGroupElements($group, $group.find('input, select'));
                inputs.data('ignore-validation', false);
                $group.data('group-validation-required', true);
            }
        },

        /**
         * @param {string|undefined} value
         * @returns {boolean}
         */
        isValueEmpty: function(value) {
            value = value ? $.trim(value) : '';
            return !value;
        },

        /**
         * @param {jQuery} $group
         */
        clearValidationErrorsAndDisableValidation: function($group) {
            var validator = $group.validate();
            var inputs = this.getGroupElements($group, $group.find('input, select'));
            inputs.data('ignore-validation', true);
            inputs.each(function(key, element) {
                    validator.hideElementErrors($(element));
                });
        },

        /**
         * @param {jQuery} $group
         * @param {jQuery} $elements
         *
         * @return {jQuery}
         */
        getGroupElements: function($group, $elements) {
            $elements.filter(function(key, element) {
                return $(element).closest('[data-validation-optional-group]').get(0) === $group.get(0);
            });

            return $elements;
        }
    };
});
