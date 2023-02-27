import BaseModel from 'oroui/js/app/models/base/model';

const SortRowsDragNDropModel = BaseModel.extend({
    constructor: function SortRowsDragNDropModel(attributes, options) {
        SortRowsDragNDropModel.__super__.constructor.call(this, attributes, options);
    },

    initialize(attributes, options) {
        this.sortOrderAttrName = options.sortOrderAttrName;

        if (this.sortOrderAttrName === void 0) {
            throw new Error('Option "sortOrderAttrName" is required for SortRowsDragNDropModel');
        }

        SortRowsDragNDropModel.__super__.initialize.call(this, attributes, options);
    },

    /**
     * Extends `get` method to have getter function for calculable attributes
     *
     * @param attr
     * @return {*}
     */
    get(attr) {
        if (typeof this[`get_${attr}`] === 'function') {
            return this[`get_${attr}`]();
        }

        return SortRowsDragNDropModel.__super__.get.call(this, attr);
    },

    /**
     * Extends `set` method to have setter function for calculable attributes
     *
     * @param attr
     * @param value
     * @param options
     * @return {*}
     */
    set(attr, value, options) {
        if (typeof attr === 'string' && typeof this[`set_${attr}`] === 'function') {
            return this[`set_${attr}`](value);
        }

        return SortRowsDragNDropModel.__super__.set.call(this, attr, value, options);
    },

    /**
     * Getter for `sortOrder` attribute
     *
     * @return {string|*}
     */
    get__sortOrder() {
        const sortOrder = this.get(this.sortOrderAttrName);
        return sortOrder !== null ? Number(sortOrder) : void 0;
    },

    /**
     * Getter for `sortOrder` attribute
     *
     * @return {string|*}
     */
    set__sortOrder(value) {
        return this.set(this.sortOrderAttrName, value !== void 0 ? value : null);
    },

    /**
     * Check if its model for a separator row
     *
     * @returns {boolean}
     */
    isSeparator() {
        return this.id === SortRowsDragNDropModel.SEPARATOR_ID;
    },

    sortOrderBackendFormatData() {
        const sortOrder = this.get('_sortOrder');
        // sortOrder value has to be either number or null (neither a string nor undefined)
        return {[this.sortOrderAttrName]: sortOrder !== void 0 ? sortOrder : null};
    }
}, {
    SEPARATOR_ID: 'separator'
});

export default SortRowsDragNDropModel;
