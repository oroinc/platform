import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import template from 'tpl-loader!oroui/templates/multiselect/parts/header/multiselect-header-view.html';
import MultiselectHeaderModel from 'oroui/js/app/views/multiselect/parts/header/multiselect-header-model';

export const cssConfig = {
    header: `multiselect__header`,
    headerCheckboxLabel: 'checkbox-label multiselect__item-checkbox',
    headerHidden: 'hidden'
};

/**
 * Multiselect header view
 * It is used to render multiselect header
 *
 * @class MultiselectHeaderView
 */
const MultiselectHeaderView = BaseMultiSelectView.extend({
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

    initialize(options) {
        this.model = new MultiselectHeaderModel({
            collection: options.collection,
            cssConfig: this.cssConfig,
            ...options
        });

        MultiselectHeaderView.__super__.initialize.call(this, options);
    },

    /**
     * Handle checkbox change event
     *
     * @param {InputEvent} event
     */
    onChanged(event) {
        if (event.currentTarget.checked) {
            this.model.selectAll();
        } else if (!this.model.getCollection().isFullSelected() && event.currentTarget.indeterminate) {
            this.model.selectAll();
        } else {
            this.model.unSelectAll();
        }
    },

    render() {
        MultiselectHeaderView.__super__.render.call(this);

        this.$('[name="select-condition"]').prop('indeterminate', this.model.isIndeterminate());

        return this;
    },

    onHiddenItems() {
        this.$el.toggleClass(this.cssConfig.headerHidden, !this.collection.getVisible().length);
    }
});

export default MultiselectHeaderView;
