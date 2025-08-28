import __ from 'orotranslation/js/translator';
import MultiSelectViewModel from 'oroui/js/app/views/multiselect/models/multiselect-view-model';

const MultiselectDropdownViewModel = MultiSelectViewModel.extend({
    defaults: {
        ...MultiSelectViewModel.prototype.defaults,
        dropdownToggleLabel: __('oro.ui.multiselect.dropdown_mode.toggle_label'),
        dropdownToggleIcon: null,
        dropdownMenuLabel: null,
        dropdownPlacement: 'bottom-start',
        dropdownAriaLabel: null,
        tooltipTitle: null,
        tooltipPlacement: 'top-center',
        enabledTooltip: true,
        showSelectedInLabel: true
    },

    constructor: function MultiselectDropdownViewModel(...args) {
        MultiselectDropdownViewModel.__super__.constructor.apply(this, args);
    },

    initialize(attrs, options) {
        if (this.get('showSelectedInLabel') && attrs.dropdownToggleLabel) {
            this.set('defaultToggleLabel', attrs.dropdownToggleLabel);
        }

        MultiselectDropdownViewModel.__super__.initialize.call(this, attrs, options);
    },

    onItemSelected() {
        MultiselectDropdownViewModel.__super__.onItemSelected.call(this);

        if (this.get('showSelectedInLabel')) {
            this.set('dropdownToggleLabel', this.collection.getLabels({
                defaults: this.get('defaultToggleLabel')
            }));
        }
    }
});

export default MultiselectDropdownViewModel;
