import MultiselectBaseModel from 'oroui/js/app/views/multiselect/models/multiselect-base-model';

const MultiselectViewModel = MultiselectBaseModel.extend({
    defaults: {
        ...MultiselectBaseModel.prototype.defaults,
        enabledHeader: true,
        enabledFooter: false,
        enabledSearch: true,
        listAriaLabel: ''
    },

    constructor: function MultiselectViewModel(...args) {
        MultiselectViewModel.__super__.constructor.apply(this, args);
    }
});

export default MultiselectViewModel;
