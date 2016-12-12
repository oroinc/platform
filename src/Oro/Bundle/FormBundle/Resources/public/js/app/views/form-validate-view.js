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

        initialize: function(options) {
            _.extend(this, _.pick(options, 'validationOptions'));
            FormValidateView.__super__.initialize.call(this, options);
        },

        render: function() {
            this.$el.validate(this.validationOptions || {});
            return this;
        },

        dispose: function() {
            delete this.validationOptions;
            FormValidateView.__super__.dispose.call(this);
        }
    });

    return FormValidateView;
});
