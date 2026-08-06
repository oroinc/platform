import mediator from 'oroui/js/mediator';
import error from 'oroui/js/error';

const viewportManager = {
    /**
     * @property {Object}
     */
    mediaTypes: null,

    normalizeJSONVarValueRegExp: /^["']|["']$|\\(["'])/g,

    /**
     * @inheritdoc
     */
    initialize() {
        this.mediaTypes = this._prepareMediaTypes(this.getBreakpoints());
        this._subscribeToAll();
    },

    /**
     * Check viewport ability
     * @param mediaTypes
     * @returns {boolean}
     */
    isApplicable(mediaTypes) {
        return [mediaTypes || 'all']
            .flat(Infinity)
            .some(item => {
                const type = this.getMediaType(item);

                if (type === void 0) {
                    return false;
                }

                return type.matches;
            });
    },

    /**
     * Get applicable breakpoint name from the list
     * @param {Array} mediaTypes
     * @returns {string}|void 0
     */
    getApplicableBreakpointName(mediaTypes) {
        return mediaTypes.find(mediaType => this.isApplicable([mediaType]));
    },

    /**
     * @param {HTMLElement} [context]
     * @param {Function} [callback]
     * @returns {any}
     */
    getBreakpoints(context, callback) {
        if (!context) {
            context = document.documentElement;
        }

        const cssProperty =
            window.getComputedStyle(context).getPropertyValue('--breakpoints').trim() || '{}';

        const result = {all: 'all', ...this.parseBreakpoints(cssProperty)};

        return typeof callback === 'function' ? callback(result) : result;
    },

    /**
     * Parses the `--breakpoints` custom property value into an object.
     *
     * @param {string} cssProperty
     * @returns {Object}
     */
    parseBreakpoints(cssProperty) {
        const normalized = cssProperty
            .replace(this.normalizeJSONVarValueRegExp, (match, unescapedQuote) => unescapedQuote || '');

        try {
            return JSON.parse(normalized);
        } catch (e) {
            error.showErrorInConsole(`Unable to parse the "--breakpoints" CSS custom property: ${e.message}`);

            return {};
        }
    },

    _prepareMediaTypes(breakpoints) {
        return Object.entries(breakpoints)
            .reduce((mediaTypes, [key, value]) => Object.assign(mediaTypes, {
                [key]: Object.assign(window.matchMedia(value), {mediaType: key})
            }), {});
    },

    _subscribe(mediaType, handler) {
        // There is no reason to subscribe on 'all' event because it never changes
        if (mediaType === 'all') {
            return;
        }

        const mql = this.getMediaType(mediaType);

        if (mql) {
            mql.addEventListener('change', handler);
        } else {
            error.showErrorInConsole(`The media type "${mediaType}" is not defined`);
        }
    },

    getMediaType(mediaType) {
        return this.mediaTypes[mediaType];
    },

    _subscribeToAll() {
        Object.keys(this.mediaTypes).forEach(mediaType => this._subscribe(mediaType, this._onChangeHandler));
    },

    _onChangeHandler(event) {
        mediator.trigger(`viewport:change`, event);
        mediator.trigger(`viewport:${event.target.mediaType}`, event);
    }
};

export default viewportManager;
