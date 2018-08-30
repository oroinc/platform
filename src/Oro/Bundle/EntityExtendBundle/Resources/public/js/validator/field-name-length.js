define(['underscore', 'oroform/js/validator/length'
], function(_, lengthValidator) {
    'use strict';

    /**
     * @export oroentityextend/js/validator/field-name-length
     */
    return [
        'Oro\\Bundle\\EntityExtendBundle\\Validator\\Constraints\\FieldNameLength',
        function(value, element, param) {
            return lengthValidator[1].call(this, value, element, param);
        },
        function(param, element) {
            return lengthValidator[2].call(this, param, element);
        }
    ];
});
