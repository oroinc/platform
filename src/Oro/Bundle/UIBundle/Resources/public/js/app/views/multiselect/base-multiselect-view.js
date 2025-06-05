import {uniq} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

const BaseMultiSelectView = BaseView.extend({
    constructor: function BaseMultiSelectView(options, ...args) {
        if (options.cssConfig) {
            this.cssConfig = BaseMultiSelectView.cssMergeConfig(options.cssConfig, this.cssConfig || {});
        }

        BaseMultiSelectView.__super__.constructor.apply(this, [options, ...args]);
    }
}, {
    /**
     * Merge css config for views
     *
     * @param {object} origin
     * @param {object} source
     * @returns {object}
     */
    cssMergeConfig(origin, source) {
        if (origin.strategy !== 'override') {
            for (const [key, value] of Object.entries(source)) {
                if (key in origin) {
                    source[key] = uniq([...value.split(' '), ...origin[key].split(' ')]).join(' ');
                }
            }

            return source;
        } else {
            return {
                ...source,
                ...origin
            };
        }
    }
});

export default BaseMultiSelectView;
