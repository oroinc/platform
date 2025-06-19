import __ from 'orotranslation/js/translator';
import MultiselectBaseModel from 'oroui/js/app/views/multiselect/models/multiselect-base-model';

const MultiselectHeaderModel = MultiselectBaseModel.extend({
    defaults: {
        ...MultiselectBaseModel.prototype.defaults,
        label: __('oro.ui.multiselect.select_all'),
        checkAllText: __('oro.ui.multiselect.select_all'),
        uncheckAllText: __('oro.ui.multiselect.deselect_all')
    },

    constructor: function MultiselectHeaderModel(...args) {
        MultiselectHeaderModel.__super__.constructor.apply(this, args);
    },

    connectCollection(collection) {
        MultiselectHeaderModel.__super__.connectCollection.call(this, collection);

        this.updateLabel();
    },

    onItemSelected() {
        MultiselectHeaderModel.__super__.onItemSelected.call(this);

        this.updateLabel();
    },

    updateLabel() {
        this.set('label', this.getCollection().isFullSelected()
            ? this.get('uncheckAllText')
            : this.get('checkAllText'));
    }
});

export default MultiselectHeaderModel;
