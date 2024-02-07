import Select2Component from 'oro/select2-component';
import DependentFieldComponent from 'orosale/js/app/components/dependent-field-component';

const Select2DependentComponent = Select2Component.extend({
    constructor: function Select2DependentComponent(...args) {
        Select2DependentComponent.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        Select2DependentComponent.__super__.initialize.call(this, options);

        this.dependentField = new DependentFieldComponent({
            _sourceElement: options._sourceElement
        });
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.dependentField.dispose();

        Select2DependentComponent.__super__.dispose.call(this);
    }
});

export default Select2DependentComponent;
