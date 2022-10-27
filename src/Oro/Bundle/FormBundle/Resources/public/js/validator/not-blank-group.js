import __ from 'orotranslation/js/translator';

const defaultParam = {
    message: 'Please add at least one item',
    selector: 'input'
};

/**
 * @export oroform/js/validator/not-blank-group
 */
export default [
    'NotBlankGroup',
    function(value, element, param) {
        param = Object.assign({}, defaultParam, param);
        const collectionElement = element.closest('[data-prototype-name]');
        return [...collectionElement.querySelectorAll(param.selector)].some(item => item.value);
    },
    function(param) {
        param = Object.assign({}, defaultParam, param);
        return __(param.message);
    }
];
