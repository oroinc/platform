import BaseModel from 'oroui/js/app/models/base/model';

/**
 * Multiselect item model
 * It is used to manage state of the multiselect item
 *
 * @class MultiSelectItemModel
 */
const MultiSelectItemModel = BaseModel.extend({
    defaults: {
        label: '',
        value: undefined,
        selected: false,
        disabled: false,
        hidden: false
    },

    idAttribute: 'value',

    constructor: function MultiSelectItemModel(...args) {
        MultiSelectItemModel.__super__.constructor.apply(this, args);
    },

    preinitialize(attrs, options) {
        if (options.defaultState && Array.isArray(options.defaultState)) {
            this.defaultState = options.defaultState
                .find(dState => dState[this.idAttribute] === attrs[this.idAttribute]);
        } else if (options.defaultState) {
            this.defaultState = this.defaultState;
        } else {
            /** default state of the item */
            this.defaultState = {...attrs};
        }
    },

    /**
     * Set state of the item
     *
     * @param {object} state
     *
     * @returns {MultiSelectItemModel}
     */
    setState(state = {}) {
        return this.set({
            ...state
        });
    },

    /**
     * Set state of the item
     *
     * @param {string} name - Property name
     *
     * @returns {boolean|string|number|object}
     */
    getState(name) {
        if (name !== void 0) {
            return this.get(name);
        }

        return this.attributes;
    },

    /**
     * Set item selected
     *
     * @returns {MultiSelectItemModel}
     */
    setSelected() {
        if (this.get('disabled')) {
            return;
        }

        return this.setState({
            selected: true
        });
    },

    /**
     * Set item unselected
     *
     * @returns {MultiSelectItemModel}
     */
    setUnSelected() {
        if (this.get('disabled')) {
            return;
        }

        return this.setState({
            selected: false
        });
    },

    /**
     * Toggle selected state of the item
     */
    toggleSelectedState() {
        this.get('selected') ? this.setUnSelected() : this.setSelected();
    },

    /**
     * Check if the item is changed
     *
     * @param {string} [prop] - Property name to check
     *
     * @returns {boolean}
     */
    isChanged(prop) {
        if (prop !== void 0 && typeof prop === 'string') {
            return this.get(prop) !== this.defaultState[prop];
        }

        return Object.entries(this.defaultState).some(([name, value]) => this.get(name) !== value);
    },

    /**
     * Check if item is active for user actions
     *
     * @returns {boolean}
     */
    isActive() {
        return !this.get('disabled') && !this.get('hidden');
    }
});

export default MultiSelectItemModel;
