define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const defaultParam = {
        invalid_message: 'This value is not valid.',
        invalid_message_parameters: {}
    };

    /**
     * @export oroform/js/validator/repeated
     */
    return [
        'Repeated',
        function(value, element, params) {
            // validator should be added to repeated field (second one)
            const attr = element.hasAttribute('data-ftid') ? 'data-ftid' : 'id';
            const id = element.getAttribute(attr);
            const secondName = params.second_name || '';
            const firstId = id.slice(0, -secondName.length) + (params.first_name || '');
            const firstElement = document.querySelector('[' + attr + '="' + firstId + '"]');
            return firstElement && (this.optional(firstElement) || value === this.elementValue(firstElement));
        },
        function(param) {
            param = Object.assign({}, defaultParam, param);
            return __(param.invalid_message, param.invalid_message_parameters);
        }
    ];
});
