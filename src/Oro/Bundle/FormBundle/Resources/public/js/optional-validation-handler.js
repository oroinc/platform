define(['jquery'], function($) {
    'use strict';

    /**
     * @export  oroform/js/optional-validation-handler
     * @class   oroform.optionalValidationHandler
     */
    return {
        /**
         * @param {jQuery} group
         * @returns {boolean}
         */
        hasNotEmptyInput: function(group) {
            var elementsSelector = 'input[type!="checkbox"][type!="radio"][type!="button"][data-required],' +
                ' input[type="radio"][data-required]:checked,' +
                ' input[type="checkbox"][data-required]:checked';
            var checkedElements = group.find(elementsSelector);
            for (var i = 0; i < checkedElements.length; i++) {
                if (!this.isValueEmpty($(checkedElements[i]).val())) {
                    return true;
                }
            }

            return false;
        },

        /**
         * @param {jQuery} group
         * @returns {boolean}
         */
        hasNotEmptySelect: function(group) {
            var elements = group.find('select[data-required]');
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
         * @param {jQuery} element
         * @param {string|undefined} value
         */
        handleGroupRequire: function(element, value) {
            var group = element.parents('[data-validation-optional-group]');

            if (this.isValueEmpty(value)) {
                if (!this.hasNotEmptyInput(group) && !this.hasNotEmptySelect(group)) {
                    group.find('label[data-required] em').hide();
                }
            } else {
                group.find('label[data-required] em').show();
            }
        },

        /**
         * @param {jQuery} element
         */
        inputHandler: function(element) {
            this.handleGroupRequire(element, element.val());
        },

        /**
         * @param {jQuery} element
         */
        selectHandler: function(element) {
            this.handleGroupRequire(element, element.find('option:selected').val());
        },

        /**
         * @constructor
         */
        initialize: function(formElement) {
            var self = this;

            var groups = formElement.find('[data-validation-optional-group]');
            var labels = groups.find('label[data-required]');

            labels.find('em').hide().html('*');
            labels.addClass('required');

            groups.on('change', 'input', function() {
                self.inputHandler($(this));
            });
            groups.on('change', 'select', function() {
                self.selectHandler($(this));
            });

            groups.each(function(index, group) {
                group = $(group);
                if (self.hasNotEmptyInput(group) || self.hasNotEmptySelect(group)) {
                    group.find('label[data-required] em').show();
                }
            });
        }
    };
});
