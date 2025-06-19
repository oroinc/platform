import BaseCollection from 'oroui/js/app/models/base/collection';
import MultiSelectItemModel from 'oroui/js/app/views/multiselect/collection/multiselect-item-model';

/**
 * Multiselect collection
 * It manages multiselect items and their states
 *
 * @class MultiSelectCollection
 */
const MultiSelectCollection = BaseCollection.extend({
    model: MultiSelectItemModel,

    constructor: function MultiSelectCollection(...args) {
        MultiSelectCollection.__super__.constructor.apply(this, args);
    },

    initialize(data, options) {
        /**
         * Default state of the collection
         */
        if (!options.defaultState) {
            this.defaultState = [...data];
        } else {
            this.defaultState = options.defaultState;
        }

        MultiSelectCollection.__super__.initialize.call(this, data, options);
    },

    /**
     * Reset collection
     * Update default state
     *
     * @param {Array} models
     * @param {Object} options
     *
     * @returns {MultiSelectCollection}
     */
    reset(models, options) {
        if (!options.defaultState) {
            this.defaultState = [...models];
        } else {
            this.defaultState = options.defaultState;
        }

        return MultiSelectCollection.__super__.reset.call(this, models, {
            ...options,
            merge: true
        });
    },

    /**
     * Select all items in the collection
     */
    selectAll() {
        this.invoke('setSelected');
    },

    /**
     * Unselect all items in the collection
     */
    unSelectAll() {
        this.invoke('setUnSelected');
    },

    getActiveItems() {
        return this.filter(model => model.isActive());
    },

    /**
     * Get selected items from the collection
     *
     * @returns {Array<MultiSelectItemModel>}
     */
    getSelected() {
        return this.getActiveItems().filter(model => model.get('selected'));
    },

    /**
     * Check if all items in the collection are selected
     *
     * @returns {boolean}
     */
    isFullSelected() {
        return this.getActiveItems().every(model => model.get('selected'));
    },

    /**
     * Check if all items in the collection are unselected
     *
     * @returns {boolean}
     */
    isFullUnSelected() {
        return this.getActiveItems().every(model => !model.get('selected'));
    },

    /**
     * Reset collection to default state
     */
    resetToDefaultState() {
        this.setState(this.defaultState);
    },

    /**
     * Check if any item in the collection has changed
     *
     * @param {string} [prop] - Property to check for changes
     * @returns {boolean}
     */
    hasChangesWeak(prop) {
        return this.some(model => model.isChanged(prop));
    },

    /**
     * Return only selected values from the collection
     *
     * @returns {Array<string>}
     */
    getSelectedValues() {
        return this.filter(model => model.get('selected')).map(model => model.get('value'));
    },

    /**
     * Update state of the collection
     *
     * @param {Object} state - State to set
     * @param {Object} [options] - Options for setting state
     * @param {boolean} [options.merge] - Whether to merge the state with existing state
     *
     * @returns {MultiSelectCollection}
     */
    setState(state, options = {}) {
        return this.set(state, {
            merge: true,
            ...options
        });
    },

    /**
     * Get visible models from the collection
     *
     * @param {string} id
     * @returns {Array<MultiSelectItemModel>|MultiSelectItemModel|null} - Array of visible models or a single model by id, or null if not found
     */
    getVisible(id) {
        const visibleModels = this.filter(model => !model.get('hidden'));
        if (!id) {
            return visibleModels;
        }

        return visibleModels.find(model => model.get('id') === id) || null;
    },

    /**
     * Trigger visibility change event
     *
     * @param  {...any} args
     */
    visibilityChange(...args) {
        this.trigger('visibilityModelsChange', this, ...args);
    },

    /**
     * Return the current state of the collection
     *
     * @returns {Array<Object>} - Array of option objects with properties:
     */
    getState() {
        return this.map(model => model.getState());
    }
});

export default MultiSelectCollection;
