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

            var labels = $group.find('label[data-required]');
            labels.addClass('required');

            var labelAsterisk = labels.find('em');
            labelAsterisk.hide().html('*');
            if (self.hasNotEmptyInput($group) || self.hasNotEmptySelect($group)) {
                labelAsterisk.show();
            }

            return true;
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
                if (!this.hasNotEmptyInput($group) && !this.hasNotEmptySelect($group)) {
                    $group.find('label[data-required] em').hide();
                    this.clearValidationErrors($group);
                }
            } else {
                $group.find('label[data-required] em').show();
                $group.find('input, select').data('ignore-validation', false);
            }
        },

        /**
         * @param {jQuery} $group
         * @returns {boolean}
         */
        hasNotEmptyInput: function($group) {
            var elementsSelector = 'input[type!="checkbox"][type!="radio"][type!="button"][data-required],' +
                ' input[type="radio"][data-required]:checked,' +
                ' input[type="checkbox"][data-required]:checked';
            var checkedElements = $group.find(elementsSelector);
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
            var elements = $group.find('select[data-required]');
            for (var i = 0; i < elements.length; i++) {
                if (!this.isValueEmpty($(elements[i]).find('option:selected').val())) {
                    return true;
                }
            }

            return false;
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
        clearValidationErrors: function($group) {
            var validator = $group.validate();
            var inputs = $group
                .find('input, select');

            inputs.data('ignore-validation', true);
            inputs.each(function(key, element) {
                    validator.hideElementErrors($(element));
                });
        },
    };
});
