define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    require('jquery.validate');

    const FormValidateView = BaseView.extend({
        keepElement: true,

        autoRender: true,

        validationOptions: null,

        events: {
            doReset: 'onReset'
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
        initialize: function(options) {
            _.extend(this, _.pick(options, 'validationOptions'));
            FormValidateView.__super__.initialize.call(this, options);
        },

        render: function() {
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

        dispose: function() {
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

    return FormValidateView;
});
