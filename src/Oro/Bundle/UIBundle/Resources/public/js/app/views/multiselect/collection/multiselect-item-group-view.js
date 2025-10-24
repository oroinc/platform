import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import MultiSelectItemView from 'oroui/js/app/views/multiselect/collection/multiselect-item-view';

import template from 'tpl-loader!oroui/templates/multiselect/collection/group.html';

export const cssConfig = {
    item: 'multiselect__item',
    itemCheckboxLabel: 'checkbox-label multiselect__item-checkbox',
    itemHidden: 'hide'
};

const MultiSelectItemGroupView = BaseCollectionView.extend({
    template,

    cssConfig,

    itemView: MultiSelectItemView,

    listSelector: '[data-role="options-group"]',

    events: {
        'click [data-role="group-label"]': 'onLabelClick'
    },

    constructor: function MultiSelectItemGroupView(...args) {
        MultiSelectItemGroupView.__super__.constructor.apply(this, args);
    },

    /**
     * Filter items in the collection based on the 'hidden' attribute.
     *
     * @param {MultiSelectModel} item
     * @returns {boolean}
     */
    filterer(item) {
        return !item.get('hidden');
    },

    /**
     * Toggle visibility of items in the collection based on the filter.
     *
     * @param {MultiSelectItemView} view
     * @param {boolean} included
     */
    filterCallback(view, included) {
        view.toggleVisibility(!included);
    },

    initialize(options) {
        this.collection = options.model.options;

        MultiSelectItemGroupView.__super__.initialize.call(this, options);
    },

    /**
     * Get data for template rendering by merging model data with parent template data
     * @returns {Object} Combined template data
     */
    getTemplateData() {
        return {
            ...this.model.toJSON(),
            ...MultiSelectItemGroupView.__super__.getTemplateData.call(this)
        };
    },

    /**
     * Handle click event on group label
     * Toggle selection state of all items in the collection
     * @param {MouseEvent} event
     */
    onLabelClick(event) {
        event.preventDefault();

        if (this.collection.isFullSelected()) {
            this.collection.unSelectAll();
        } else {
            this.collection.selectAll();
        }
    },

    /**
     * Toggle visibility of the item
     * @param {boolean} hidden
     */
    toggleVisibility() {
        this.filter();

        this.model.set('hidden', this.visibleItems.length === 0);
        this.$el.toggleClass(this.cssConfig.itemHidden, this.model.options.getVisible().length === 0);
    },

    getActiveElement() {
        console.log(this.$('input[type="checkbox"]:visible:tabbable').toArray());

        return this.$('input[type="checkbox"]:visible:tabbable');
    }
});

export default MultiSelectItemGroupView;
