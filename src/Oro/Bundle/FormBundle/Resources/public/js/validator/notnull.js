import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import 'jquery.validate';

const defaultParam = {
    message: 'This value should not be null.'
};

/**
 * @export oroform/js/validator/notnull
 */
export default [
    'NotNull',
    function(...args) {
        return $.validator.methods.required.apply(this, args);
    },
    function(param) {
        param = Object.assign({}, defaultParam, param);
        return __(param.message);
    }
];
