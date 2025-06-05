import MultiselectView, {cssConfig as baseCssConfig} from 'oroui/js/app/views/multiselect/multiselect-view';
import MultiselectDropdownViewModel from 'oroui/js/app/views/multiselect/models/multiselect-dropdown-view-model';
import template from 'tpl-loader!oroui/templates/multiselect/multiselect-dropdown-view.html';

export const cssConfig = {
    ...baseCssConfig,
    dropdown: 'dropdown',
    dropdownToggleBtn: 'btn btn--outlined dropdown-toggle',
    dropdownMenu: 'dropdown-menu multiselect__dropdown-menu',
    dropdownMenuLabel: 'multiselect__dropdown-menu-title'
};

/**
 * Multiselect dropdown view
 * It is used to render multiselect component inside dropdown
 *
 * @class MultiselectDropdownView
 */
const MultiselectDropdownView = MultiselectView.extend({
    Model: MultiselectDropdownViewModel,

    cssConfig,

    template,

    constructor: function MultiselectDropdownView(...args) {
        MultiselectDropdownView.__super__.constructor.apply(this, args);
    },

    /**
     * Update current root element
     * @returns {jQuery}
     */
    getRootElement() {
        return this.$('[data-role="content"]');
    }
});

export default MultiselectDropdownView;
