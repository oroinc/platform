import BaseMultiSelectView from 'oroui/js/app/views/multiselect/base-multiselect-view';
import template from 'tpl-loader!oroui/templates/multiselect/parts/footer/multiselect-footer-view.html';
import MultiselectFooterModel from 'oroui/js/app/views/multiselect/parts/footer/multiselect-footer-model';

export const cssConfig = {
    footer: `multiselect__footer`,
    footerResetBtn: 'btn btn--flat',
    footerHidden: 'hidden'
};

/**
 * Multiselect footer view
 * It is used to render multiselect footer
 *
 * @class MultiselectFooterView
 */
const MultiSelectFooterView = BaseMultiSelectView.extend({
    Model: MultiselectFooterModel,

    optionNames: BaseMultiSelectView.prototype.optionNames.concat(['cssConfig']),

    cssConfig,

    template,

    className() {
        return this.cssConfig.footer;
    },

    events: {
        'click [data-role="reset"]': 'resetState'
    },

    listen: {
        'change:enabledReset model': 'render',
        'reset collection': 'render',
        'change:hidden collection': 'onHiddenItems'
    },

    constructor: function MultiSelectFooterView(...args) {
        MultiSelectFooterView.__super__.constructor.apply(this, args);
    },

    resetState(event) {
        event.stopPropagation();

        this.collection.resetToDefaultState();
    },

    onHiddenItems() {
        this.$el.toggleClass(this.cssConfig.footerHidden, !this.collection.getVisible().length);
    }
});

export default MultiSelectFooterView;
