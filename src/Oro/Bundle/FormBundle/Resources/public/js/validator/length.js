define(function(require) {
    'use strict';

    const numberValidator = require('oroform/js/validator/number');

    const defaultParam = {
        exactMessage: 'This value should have exactly {{ limit }} character.|' +
            'This value should have exactly {{ limit }} characters.',
        maxMessage: 'This value is too long. It should have {{ limit }} character or less.|' +
            'This value is too long. It should have {{ limit }} characters or less.',
        minMessage: 'This value is too short. It should have {{ limit }} character or more.|' +
            'This value is too short. It should have {{ limit }} characters or more.'
    };

    /**
     * @export oroform/js/validator/length
     */
    return [
        'Length',
        function(value, element, param) {
            return this.optional(element) || numberValidator[1].call(this, value.length, element, param);
        },
        function(param, element) {
            const value = this.elementValue(element);
            const placeholders = {};
            param = Object.assign({}, defaultParam, param);
            placeholders.value = value;
            return numberValidator[2].call(this, param, element, value.length, placeholders);
        }
    ];
});
