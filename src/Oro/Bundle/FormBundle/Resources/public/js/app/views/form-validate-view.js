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
         * @inheritDoc
         */
        constructor: function FormValidateView(options) {
            FormValidateView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'validationOptions'));
            FormValidateView.__super__.initialize.call(this, options);
        },

        render: function() {
            this.validator = this.$el.validate(this.validationOptions || {});
            return this;
        },

        onReset: function() {
            this.validator.resetForm();
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
