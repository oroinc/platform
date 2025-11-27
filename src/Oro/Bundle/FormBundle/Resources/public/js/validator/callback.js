import __ from 'orotranslation/js/translator';

const defaultParam = {
    message: 'This value is not valid.',
    callback: null
};

/**
 * @export oroform/js/validator/callback
 */
export default [
    'Callback',
    function(value, element, param) {
        if (typeof param.callback !== 'function') {
            throw new Error('Validation[Callback method]: "callback" param is not a function');
        }
        return param.callback(value, element, param);
    },
    function(param, element) {
        const value = this.elementValue(element);
        const placeholders = {};
        param = Object.assign({}, defaultParam, param);
        placeholders.value = value;
        return __(param.message, placeholders);
    }
];
