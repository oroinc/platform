import BaseModel from 'oroui/js/app/models/base/model';

/**
 * Multiselect base model
 * It is used to manage state of the multiselect component
 *
 * @class MultiSelectBaseModel
 */
const MultiSelectBaseModel = BaseModel.extend({
    defaults: {
        selectedCount: 0,
        cssConfig: null
    },

    constructor: function MultiSelectBaseModel(...args) {
        MultiSelectBaseModel.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.connectCollection(options.collection);
    },

    /**
     * Select all in current collection
     */
    selectAll() {
        this.getCollection().selectAll();
    },

    /**
     * Unselect all in current collection
     */
    unSelectAll() {
        this.getCollection().unSelectAll();
    },

    /**
     * Is items not fully selected
     *
     * @returns {boolean}
     */
    isIndeterminate() {
        return !this.getCollection().isFullSelected() && !this.getCollection().isFullUnSelected();
    },

    /**
     * Setup collection
     *
     * @param {MultiSelectCollection} collection
     *
     * @returns {MultiSelectCollection}
     */
    connectCollection(collection) {
        this.collection = collection;

        this.listenTo(this.collection, 'reset change:selected', this.onItemSelected);

        this.onItemSelected();

        return this.collection;
    },

    /**
     * Update selected items count
     */
    onItemSelected() {
        this.set('selectedCount', this.collection.getSelected().length);
    },

    /**
     * Return attached collection
     *
     * @returns {MultiSelectCollection}
     */
    getCollection() {
        return this.collection;
    }
});

export default MultiSelectBaseModel;
