import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import template from 'tpl-loader!oroui/templates/multiselect/parts/header/multiselect-header-view.html';
import MultiselectHeaderModel from 'oroui/js/app/views/multiselect/parts/header/multiselect-header-model';

export const cssConfig = {
    header: `multiselect__header`,
    headerCheckboxLabel: 'checkbox-label multiselect__item-checkbox',
    headerHidden: 'hide',
    selectAll: 'multiselect__select-none',
    selectNone: 'multiselect__select-all'
};

/**
 * Multiselect header view
 * It is used to render multiselect header
 *
 * @class MultiselectHeaderView
 */
const MultiselectHeaderView = BaseMultiSelectView.extend({
    Model: MultiselectHeaderModel,

    optionNames: BaseMultiSelectView.prototype.optionNames.concat(['cssConfig']),

    cssConfig,

    template,

    events: {
        'change [name="select-condition"]': 'onChanged'
    },

    listen: {
        'change model': 'render',
        'reset collection': 'render',
        'change:hidden collection': 'onHiddenItems'
    },

    className() {
        return this.cssConfig.header;
    },

    constructor: function MultiselectHeaderView(...args) {
        MultiselectHeaderView.__super__.constructor.apply(this, args);
    },

    /**
     * Handle checkbox change event
     */
    onChanged() {
        if (!this.model.isFullSelected()) {
            this.model.selectAll();
        } else {
            this.model.unSelectAll();
        }
    },

    render() {
        MultiselectHeaderView.__super__.render.call(this);

        this.$('[name="select-condition"]').prop('indeterminate', this.model.isIndeterminate());
        this.$('[data-role="mass-select"]')
            .toggleClass(this.cssConfig.selectNone, this.model.isFullSelected())
            .toggleClass(this.cssConfig.selectAll, !this.model.isFullSelected());

        return this;
    },

    onHiddenItems() {
        this.$el.toggleClass(this.cssConfig.headerHidden, !this.collection.getVisible().length);
    }
});

export default MultiselectHeaderView;
