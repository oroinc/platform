import Backgrid from 'backgrid';

const InputCellValidationEditor = Backgrid.InputCellEditor.extend({
    constructor: function InputCellValidationEditor(attributes, options) {
        return InputCellValidationEditor.__super__.constructor.call(this, attributes, options);
    },

    attributes() {
        return {
            'type': 'text',
            'name': `quantity_${this.model.cid}`,
            'data-validation': JSON.stringify(this.model.get('constraints')),
            'data-floating-error': ''
        };
    },

    className: 'input-editor',

    /**
     * @inheritdoc
     */
    initialize(options) {
        InputCellValidationEditor.__super__.initialize.call(this, options);

        // Validate element after it is inserted to the page
        this.listenToOnce(this.model, 'gridIsReady', () => {
            this.validateElement();
        });
        // Validate element after re-rendering itself
        this.listenToOnce(this.model, 'backgrid:editing', () => {
            this.validateElement();
        });
    },

    validateElement() {
        const validator = this.$el.closest('form').data('validator');

        if (validator) {
            return validator.element(this.$el);
        }

        return true;
    },

    resetValidation() {
        const validator = this.$el.closest('form').data('validator');

        if (validator) {
            const $error = validator.errorsFor(this.$el);

            if ($error.length) {
                validator.hideThese($error);
                validator.resetElements(this.$el);
            }
        }
    },

    /**
     * @inheritdoc
     */
    render() {
        const value = this.model.get(this.column.get('name'));
        const invalidValue = this.model.get('invalidValue');

        this.$el.val(invalidValue !== void 0 ? invalidValue : value);
        return this;
    },

    /**
     * @inheritdoc
     */
    saveOrCancel(e) {
        if (this.validateElement()) {
            this.model.unset('invalidValue');
            InputCellValidationEditor.__super__.saveOrCancel.call(this, e);
        } else {
            this.model.set('invalidValue', this.$el.val().trim());
        }
    }
});

export default InputCellValidationEditor;
