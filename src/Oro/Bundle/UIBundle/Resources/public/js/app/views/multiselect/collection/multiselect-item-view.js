import {debounce} from 'underscore';
import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import template from 'tpl-loader!oroui/templates/multiselect/collection/item.html';
import manageFocus from 'oroui/js/tools/manage-focus';

export const cssConfig = {
    item: 'multiselect__item',
    itemCheckboxLabel: 'checkbox-label multiselect__item-checkbox',
    itemCheckbox: '',
    itemHidden: 'hide'
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

    multiple: true,

    template,

    events() {
        return {
            [`change input[type="${this.model.get('multiple') ? 'checkbox' : 'radio'}"]`]: 'onCheckboxChanged',
            click: 'onClickHandler'
        };
    },

    listen: {
        'change model': 'render'
    },

    constructor: function MultiSelectItemView(...args) {
        this.toggleState = debounce(this.toggleState);
        MultiSelectItemView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.model.set('cssConfig', this.cssConfig);

        MultiSelectItemView.__super__.initialize.call(this, options);
    },

    onClickHandler() {
        if (this.model.isActive()) {
            this.toggleState(true);
        }
    },

    onCheckboxChanged(event) {
        this.toggleState(event.currentTarget.checked);
    },

    toggleState(state, options = {}) {
        if (this.model.get('multiple') === false) {
            this.model.collection.each(model => model.setState({
                selected: false
            }, {
                silent: true
            }));
        }

        this.model.set('selected', state, options);
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
