import __ from 'orotranslation/js/translator';

const defaultParam = {
    message: 'Please add at least one item'
};

/**
 * @export oroform/js/validator/not-blank-group
 */
export default [
    'NotBlankGroup',
    function(value, element) {
        const collectionName = element.getAttribute('data-collection-name');
        const collectionElement = element.closest(`[data-collection="${collectionName}"]`);
        return [...collectionElement.querySelectorAll('input')].some(item => item.value);
    },
    function(param) {
        param = Object.assign({}, defaultParam, param);
        return __(param.message);
    }
];
