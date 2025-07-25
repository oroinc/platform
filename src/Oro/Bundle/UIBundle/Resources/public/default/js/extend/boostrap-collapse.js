import $ from 'jquery';
import {macros} from 'underscore';
import 'oroui/js/extend/bootstrap/bootstrap-collapse';

const Collapse = $.fn.collapse.Constructor;
const originalDefault = Collapse.Default;
const {_getConfig: _getConfigOrigin, captionUpdate: captionUpdateOrigin} = Collapse.prototype;

const ExtendedDefault = {
    ...originalDefault,
    triggerIconShow: '',
    triggerIconHide: ''
};

/**
 * @param {Object} config
 * @private
 */
Collapse.prototype._getConfig = function(config) {
    config = _getConfigOrigin.call(this, config);

    return {
        ...ExtendedDefault,
        ...config
    };
};

/**
 * Update trigger element attributes according to state
 *
 * @param {boolean} state
 * @param {jQuery.Element} $el
 */
Collapse.prototype.captionUpdate = function(state, $el) {
    captionUpdateOrigin.call(this, state, $el);

    const $iconEl = $el.find('[data-icon]');
    const svgClass = $iconEl.find('.theme-icon').attr('class');
    const {triggerIconShow, triggerIconHide} = this._config;

    if (state && triggerIconShow) {
        $iconEl.html(macros('oroui::renderIcon')({'name': triggerIconShow, 'class': svgClass}));
    } else if (!state && triggerIconHide) {
        $iconEl.html(macros('oroui::renderIcon')({'name': triggerIconHide, 'class': svgClass}));
    }
};
