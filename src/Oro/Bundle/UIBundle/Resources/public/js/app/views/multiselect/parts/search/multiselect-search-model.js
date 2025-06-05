import __ from 'orotranslation/js/translator';
import MultiSelectBaseModel from 'oroui/js/app/views/multiselect/models/multiselect-base-model';

const MultiselectSearchModel = MultiSelectBaseModel.extend({
    defaults: {
        ...MultiSelectBaseModel.prototype.defaults,
        placeholder: __('oro.ui.multiselect.search.placeholder'),
        searchFieldAriaLabel: __('oro.ui.multiselect.search.aria_label'),
        searchResetBtnAriaLabel: __('oro.ui.multiselect.search.reset_btn.aria_label'),
        maxItemsForShowSearchBar: 7
    },

    constructor: function MultiselectSearchModel(...args) {
        MultiselectSearchModel.__super__.constructor.apply(this, args);
    }
});

export default MultiselectSearchModel;
