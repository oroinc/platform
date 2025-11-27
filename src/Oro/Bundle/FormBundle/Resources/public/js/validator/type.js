import __ from 'orotranslation/js/translator';
import numberFormatter from 'orolocale/js/formatter/number';
const defaultParam = {
    message: 'This value should be of type {{ type }}.'
};

/**
 * @export oroform/js/validator/type
 */
export default [
    'Type',
    function(value, element, param) {
        let result;
        switch (param.type) {
            case 'integer':
                const valueNumber = Number(value).toFixed();
                result = String(valueNumber) === value;
                break;
            case 'float':
            case 'numeric':
                result = !isNaN(numberFormatter.unformatStrict(value));
                break;
            default:
                result = true;
        }
        return this.optional(element) || result;
    },
    function(param) {
        param = Object.assign({}, defaultParam, param);
        return __(param.message, {type: param.type});
    }
];
