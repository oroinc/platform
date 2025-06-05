import __ from 'orotranslation/js/translator';
import MultiSelectViewModel from 'oroui/js/app/views/multiselect/models/multiselect-view-model';

const MultiselectDropdownViewModel = MultiSelectViewModel.extend({
    defaults: {
        ...MultiSelectViewModel.prototype.defaults,
        dropdownToggleLabel: __('oro.ui.multiselect.dropdown_mode.toggle_label'),
        dropdownToggleIcon: null,
        dropdownMenuLabel: null
    },

    constructor: function MultiselectDropdownViewModel(...args) {
        MultiselectDropdownViewModel.__super__.constructor.apply(this, args);
    }
});

export default MultiselectDropdownViewModel;
