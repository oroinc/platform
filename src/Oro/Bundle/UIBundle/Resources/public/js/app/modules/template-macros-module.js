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
    renderLink: require('tpl-loader!oroui/templates/macros/link.html'),

    /**
     * Renders content depended on direction
     *
     * Entered content will be wrapped by html element (SPAN) with applied attributes
     * @example
     * // returns <span dir="rtl">4נינג'ות</dir>
     * renderDirection({content: '4נינג'ות', dir: 'rtl'})
     * @example
     * // returns <span dir="ltr">/text</dir>
     * renderDirection({content: '/text'})
     * @param {Object} data
     * @param {Object|string} data.content
     * @param {Object|string} data.class optional
     * @param {Object|string} data.dir optional
     */
    renderDirection: require('tpl-loader!oroui/templates/macros/direction.html'),

    /**
     * Renders svg icon
     *
     * @param {Object} data
     * @param {Object|string} data.id
     * @param {Object|string} data.width optional
     * @param {Object|string} data.height optional
     * @param {Object|string} data.role optional
     * @param {Object|string} data.fill optional
     * @param {string?} data.ariaHidden optional
     */
    renderIcon: require('tpl-loader!oroui/templates/macros/svg-icon.html'),

    /**
     * Renders a status label
     * @param {string} data.label
     * @param {string} data.current
     * @param {Object} data.map
     */
    renderStatus: require('tpl-loader!oroui/templates/macros/status.html')
});
