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

    listen: {
        'change:enabledTooltip model': 'initTooltip',
        'change:dropdownToggleLabel model': 'updateBtnLabel'
    },

    constructor: function MultiselectDropdownView(...args) {
        MultiselectDropdownView.__super__.constructor.apply(this, args);
    },

    /**
     * Update current root element
     * @returns {jQuery}
     */
    getRootElement() {
        return this.$('[data-role="content"]');
    },

    render() {
        MultiselectDropdownView.__super__.render.call(this);

        this.initTooltip();

        return this;
    },

    updateBtnLabel() {
        this.$('[data-role="toggle-label"]').text(this.model.get('dropdownToggleLabel'));
    },

    initTooltip() {
        if (!this.getToogleButton().data('bs.tooltip')) {
            this.getToogleButton().tooltip({
                placement: this.model.get('tooltipPlacement')
            });
        }

        this.getToogleButton().tooltip(this.model.get('enabledTooltip') ? 'enable' : 'disable');
    },

    /**
     * Enable showing tooltip on toggle button
     *
     * @param {boolean} enabled
     */
    buttonTooltipEnabled(enabled = true) {
        this.model.set('enabledTooltip', enabled);

        return this;
    },

    getToogleButton() {
        return this.$('[data-toggle="dropdown"]');
    },

    show() {
        this.getToogleButton().dropdown('show');
    },

    hide() {
        this.getToogleButton().dropdown('hide');
    }
});

export default MultiselectDropdownView;
