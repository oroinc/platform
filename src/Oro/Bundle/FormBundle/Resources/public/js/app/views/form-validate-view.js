define(function(require) {
    'use strict';

    var FormValidateView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.validate');

    FormValidateView = BaseView.extend({
        keepElement: true,

        autoRender: true,

        validationOptions: null,

        /**
         * @inheritDoc
         */
        constructor: function FormValidateView() {
            FormValidateView.__super__.constructor.apply(this, arguments);
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

        dispose: function() {
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
