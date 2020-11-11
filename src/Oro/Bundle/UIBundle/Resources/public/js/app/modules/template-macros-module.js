import {macros} from 'underscore';

macros('oroui', {
    /**
     * Renders tel link for a phone
     *
     * @param {Object} data
     * @param {Object|string} data.phone
     * @param {string?} data.title optional
     */
    renderPhone: require('tpl-loader!oroui/templates/macros/phone.html')
});
