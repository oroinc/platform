import MultiSelectItemModel from './multiselect-item-model';
import MultiSelectCollection from 'oroui/js/app/views/multiselect/collection/multiselect-collection';

/**
 * Multiselect item group model
 * It is used to manage state of the multiselect item group and its child options
 *
 * @class MultiSelectItemGroupModel
 * @extends MultiSelectItemModel
 */
const MultiSelectItemGroupModel = MultiSelectItemModel.extend({
    constructor: function MultiSelectItemGroupModel(...args) {
        MultiSelectItemGroupModel.__super__.constructor.apply(this, args);
    },

    preinitialize(attrs, options) {
        MultiSelectItemGroupModel.__super__.preinitialize.call(this, attrs, options);

        if (attrs.type === MultiSelectItemModel.TYPES.GROUP) {
            this.options = new MultiSelectCollection(attrs.options);
        }
    },

    initialize(attrs, options) {
        MultiSelectItemGroupModel.__super__.initialize.call(this, attrs, options);

        this.listenTo(this.options, 'all', (...args) => this.trigger(...args));
        this.listenTo(this, 'change:options', this.onOptionsChange);
    },

    /**
     * Handle changes to the options collection
     *
     * @param {Object} options - The new options state
     */
    onOptionsChange(model, value, options) {
        this.options.setState(value, options);
    },

    /**
     * Set state of the item
     *
     * @param {object} state
     *
     * @returns {MultiSelectItemModel}
     */
    setState(state = {}, options = {}) {
        return this.set({
            ...state
        }, options);
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
     * Set all items in the group as selected
     *
     * @returns {void}
     */
    setSelected() {
        this.options.invoke('setSelected');
    },

    /**
     * Set all items in the group as unselected
     *
     * @returns {void}
     */
    setUnSelected() {
        this.options.invoke('setUnSelected');
    },

    /**
     * Toggle selected state of all items in the group
     *
     * @returns {void}
     */
    toggleSelectedState() {
        this.get('selected') ? this.setUnSelected() : this.setSelected();
    },

    /**
     * Check if the item group is changed from its default state
     *
     * @param {string} [prop] - Property name to check for changes
     *
     * @returns {boolean} True if the item or property has changed
     */
    isChanged(prop) {
        if (prop !== void 0 && typeof prop === 'string') {
            return this.get(prop) !== this.defaultState[prop];
        }

        return Object.entries(this.defaultState).some(([name, value]) => this.get(name) !== value);
    },

    /**
     * Set the hidden state for all items in the group
     *
     * @param {boolean} hidden - Whether to hide the items
     * @returns {void}
     */
    setHidden(hidden) {
        this.options.invoke('setHidden', hidden);

        this.set('hidden', !this.isActive());
    },

    /**
     * Check if this item is a group
     *
     * @returns {boolean} Always returns true for group models
     */
    isGroup() {
        return true;
    },

    /**
     * Get the total count of all items in the group
     *
     * @returns {number} The number of items in the group
     */
    getAllItemsCount() {
        return this.options.length;
    },

    /**
     * Get all items in the group
     *
     * @returns {Array} All items in the group
     */
    getItems() {
        return this.options.getAllItems();
    },

    /**
     * Check if any option in the group is active
     *
     * @returns {boolean} True if at least one option is active
     */
    isActive() {
        return this.options.some(model => model.isActive());
    },

    isSelected() {
        return this.options.getActiveItems().some(model => model.isSelected());
    },

    getValue() {
        return this.options.filter(model => model.isSelected()).map(model => model.getValue());
    },

    /**
     * Check if the item is changed
     *
     * @param {string} [prop] - Property name to check
     *
     * @returns {boolean}
     */
    isChanged(prop) {
        return this.options.some(model => model.isChanged(prop));
    }
});

export default MultiSelectItemGroupModel;
