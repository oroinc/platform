import EntityFieldUtil from 'oroentity/js/entity-field-choice-util';
import Select2EntityFieldComponent from 'oro/select2-entity-field-component';

const Select2EntityFieldChoiceComponent = Select2EntityFieldComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function Select2EntityFieldChoiceComponent(options) {
        Select2EntityFieldChoiceComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.util = new EntityFieldUtil(options._sourceElement);
        Select2EntityFieldChoiceComponent.__super__.initialize.call(this, options);
    }
});

export default Select2EntityFieldChoiceComponent;
