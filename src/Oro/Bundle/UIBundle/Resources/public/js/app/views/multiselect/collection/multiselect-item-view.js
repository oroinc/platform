import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import template from 'tpl-loader!oroui/templates/multiselect/collection/item.html';
import manageFocus from 'oroui/js/tools/manage-focus';

export const cssConfig = {
    item: 'multiselect__item',
    itemCheckboxLabel: 'checkbox-label multiselect__item-checkbox',
    itemHidden: 'hidden'
};

/**
 * Multiselect item view
 * It is used to render multiselect item
 *
 * @class MultiSelectItemView
 */
const MultiSelectItemView = BaseMultiSelectView.extend({
    optionNames: BaseMultiSelectView.prototype.optionNames.concat(['cssConfig']),

    cssConfig,

    className() {
        return this.cssConfig.item;
    },

    template,

    events: {
        'change input[type="checkbox"]': 'onCheckboxChanged'
    },

    listen: {
        'change model': 'render'
    },

    constructor: function MultiSelectItemView(...args) {
        MultiSelectItemView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.model.set('cssConfig', this.cssConfig);

        MultiSelectItemView.__super__.initialize.call(this, options);
    },

    onCheckboxChanged(event) {
        this.model.set('selected', event.currentTarget.checked);
    },

    render() {
        const keepFocus = this.$el.has(':focus-within').length;

        MultiSelectItemView.__super__.render.call(this);

        if (keepFocus) {
            this.focusItem();
        }

        return this;
    },

    /**
     * Focus on the item
     */
    focusItem() {
        manageFocus.focusTabbable(this.$el);
    },

    /**
     * Toggle visibility of the item
     * @param {boolean} hidden
     */
    toggleVisibility(hidden) {
        this.$el.toggleClass(this.cssConfig.itemHidden, hidden);
    },

    /**
     * Return active element in the view
     *
     * @returns {HTMLElement|null}
     */
    getActiveElement() {
        return this.$('input[type="checkbox"]:visible:tabbable')[0];
    }
});

export default MultiSelectItemView;
