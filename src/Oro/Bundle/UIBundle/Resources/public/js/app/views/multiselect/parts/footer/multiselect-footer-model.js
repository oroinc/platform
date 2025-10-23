import __ from 'orotranslation/js/translator';
import MultiSelectBaseModel from 'oroui/js/app/views/multiselect/models/multiselect-base-model';

const MultiselectFooterModel = MultiSelectBaseModel.extend({
    defaults: {
        ...MultiSelectBaseModel.prototype.defaults,
        enabledReset: false,
        resetButtonLabel: __('oro.ui.multiselect.footer.reset_button_label'),
        resetButtonIcon: 'undo'
    },

    constructor: function MultiselectFooterModel(...args) {
        MultiselectFooterModel.__super__.constructor.apply(this, args);
    },

    connectCollection(collection) {
        MultiselectFooterModel.__super__.connectCollection.call(this, collection);

        this.listenTo(this.getCollection(), 'reset', this.onCollectionReset);
    },

    onItemSelected() {
        MultiselectFooterModel.__super__.onItemSelected.call(this);

        this.set('enabledReset', this.collection.hasChangesWeak());
    },

    onCollectionReset() {
        this.set('enabledReset', this.collection.hasChangesWeak());
    }
});

export default MultiselectFooterModel;
