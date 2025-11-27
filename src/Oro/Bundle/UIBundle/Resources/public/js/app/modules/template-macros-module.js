import {macros} from 'underscore';
import renderPhone from 'tpl-loader!oroui/templates/macros/phone.html';
import renderLink from 'tpl-loader!oroui/templates/macros/link.html';
import renderDirection from 'tpl-loader!oroui/templates/macros/direction.html';
import renderIcon from 'tpl-loader!oroui/templates/macros/svg-icon.html';
import renderStatus from 'tpl-loader!oroui/templates/macros/status.html';
import renderTooltipStatus from 'tpl-loader!oroui/templates/macros/tooltip-status.html';

macros('oroui', {
    /**
     * Renders tel link for a phone
     *
     * @param {Object} data
     * @param {Object|string} data.phone
     * @param {string?} data.title optional
     */
    renderPhone,

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
    renderLink,

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
    renderDirection,

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
    renderIcon,

    /**
     * Renders a status label
     * @param {string} data.label
     * @param {string} data.current
     * @param {Object} data.map
     */
    renderStatus,

    /**
     * Renders a status label as a tooltip
     * @param {string} data.label
     * @param {string} data.current
     * @param {Object} data.map
     * @param {string} data.offset
     */
    renderTooltipStatus
});
