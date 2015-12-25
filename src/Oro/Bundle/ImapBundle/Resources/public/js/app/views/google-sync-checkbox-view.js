define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var GoogleSyncCheckboxView;

    GoogleSyncCheckboxView = BaseView.extend({
        $errorMessage: null,

        $successMessage: null,

        token: null,

        canShowMessage: false,

        events: {
            'change input[type=checkbox]' : 'onChangeCheckBox'
        },

        initialize: function(options) {
            this.$errorMessage = this.$el.find(options.errorMessage);
            this.$successMessage = this.$el.find(options.successMessage);

            this.listenTo(this, 'change:canShowMessage', this.render);
        },

        render: function() {
            if (this.canShowMessage) {
                this.showMessage();
            } else {
                this.hideMessages();
            }
        },

        /**
         * Set response from google oAuth2.0 authentication
         * @param token
         */
        setToken: function(token) {
            this.token = token;
        },

        /**
         * Handler event change for checkbox
         * @param e
         */
        onChangeCheckBox: function(e) {
            if ($(e.target).is(':checked')) {
                this.canShowMessage = true;
                this.trigger('requestToken');
            } else {
                this.canShowMessage = false;
                this.trigger('change:canShowMessage');
            }
        },

        /**
         * Show success or error message
         */
        showMessage: function() {
            this.hideMessages();

            if (this.token && !this.token.error) {
                this.showSuccess();
            } else {
                this.showError();
            }
        },

        /**
         * show success message
         */
        hideMessages: function() {
            this.$errorMessage.hide();
            this.$successMessage.hide();
        },

        /**
         * show success message
         */
        showSuccess: function() {
            this.$successMessage.show();
        },

        /**
         * show error message
         */
        showError: function() {
            this.$errorMessage.show();
        }
    });

    return GoogleSyncCheckboxView;
});
