import {macros} from 'underscore';

macros('oroui', {
    /**
     * Renders tel link for a phone
     *
     * @param {Object} data
     * @param {Object|string} data.phone
     * @param {string?} data.title optional
     */
    renderPhone: require('tpl-loader!oroui/templates/macros/phone.html'),

    /**
     * Renders link
     *
     * @param {Object} data
     * @param {Object|string} data.href
     * @param {Object|string} data.text
     * @param {Object|string} data.target
     * @param {string?} data.id optional
     * @param {string?} data.class optional
     * @param {string?} data.title optional
     */
    renderLink: require('tpl-loader!oroui/templates/macros/link.html')
});
