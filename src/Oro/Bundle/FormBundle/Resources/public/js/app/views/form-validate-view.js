import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import 'jquery.validate';

const FormValidateView = BaseView.extend({
    keepElement: true,

    autoRender: true,

    validationOptions: null,

    events: {
        'doReset': 'onReset',
        'invalid-content:shown': 'focusInvalid'
    },

    listen: {
        'page:afterChange mediator': 'focusInvalid',
        'page:afterPagePartChange mediator': 'focusInvalid'
    },

    /**
     * @inheritdoc
     */
    constructor: function FormValidateView(options) {
        FormValidateView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, 'validationOptions'));
        FormValidateView.__super__.initialize.call(this, options);
    },

    render() {
        if (this.$el.data('validator')) {
            // form already has initialized validator
            return this;
        }

        this._deferredRender();
        this.validator = this.$el.validate({
            ...(this.validationOptions || {}),
            onMethodsLoaded: () => this._resolveDeferredRender()
        });

        return this;
    },

    onReset: function() {
        if (this.validator) {
            this.validator.resetForm();
        }
    },

    focusInvalid() {
        _.defer(() => {
            if (!this.disposed && this.validator) {
                this.validator.focusInvalid();
            }
        });
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.validationOptions;
        if (this.validator) {
            this.validator.destroy();
            delete this.validator;
        }
        FormValidateView.__super__.dispose.call(this);
    }
});

export default FormValidateView;
