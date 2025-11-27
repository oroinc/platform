import __ from 'orotranslation/js/translator';
import patterns from 'oroui/js/tools/patterns';
import 'jquery.validate';

const defaultParam = {
    message: 'This value is not a valid email address.'
};

/**
 * @export oroform/js/validator/email
 */
export default [
    'Email',
    function(value, element, param) {
        const {mode, normalizer = null} = param || {};
        // only `html5` and `loose` modes are supported, any other mode is treated as `loose`
        const regExp = mode === 'html5' && !normalizer ? patterns.email_html5 : patterns.email_loose;
        return this.optional(element) || regExp.test(value);
    },
    function(param, element) {
        const value = this.elementValue(element);
        const placeholders = {};
        param = Object.assign({}, defaultParam, param);
        placeholders.value = value;
        return __(param.message, placeholders);
    }
];
