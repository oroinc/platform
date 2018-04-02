define(function(require) {
    'use strict';

    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var GoogleSyncCheckboxView;

    GoogleSyncCheckboxView = BaseView.extend({
        $errorMessage: null,

        $successMessage: null,

        $googleErrorMessage: null,

        $googleWarningMessage: null,

        token: null,

        googleErrorMessage: '',

        canShowMessage: false,

        events: {
            'change input[type=checkbox]': 'onChangeCheckBox'
        },

        listen: {
            'change:canShowMessage': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function GoogleSyncCheckboxView() {
            GoogleSyncCheckboxView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$errorMessage = this.$el.find(options.errorMessage);
            this.$successMessage = this.$el.find(options.successMessage);
            this.$googleErrorMessage = this.$el.find(options.googleErrorMessage);
            this.$googleWarningMessage = this.$el.find(options.googleWarningMessage);
        },

        render: function() {
            this.$googleErrorMessage.html(this.googleErrorMessage);

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
         * Set error message from google API
         * @params {string} message
         */
        setGoogleErrorMessage: function(message) {
            this.googleErrorMessage = message;
        },

        /**
         * Reset error message from google API
         */
        resetGoogleErrorMessage: function() {
            this.googleErrorMessage = '';
        },

        /**
         * Reset property token
         */
        resetToken: function() {
            this.token = null;
        },

        /**
         * Handler event change for checkbox
         * @param e
         */
        onChangeCheckBox: function(e) {
            this.resetGoogleErrorMessage();
            this.resetToken();
            this.hideMessages();

            if ($(e.target).is(':checked')) {
                this.canShowMessage = true;
                this.trigger('requestToken');
            } else {
                this.canShowMessage = false;
                this.$googleWarningMessage.show();
            }
        },

        /**
         * Show success or error message
         */
        showMessage: function() {
            this.hideMessages();

            if (this.googleErrorMessage.length > 0) {
                this.unCheck();
                this.showGoogleError();
            } else if (this.token && !this.token.error) {
                this.showSuccess();
            } else {
                this.unCheck();
                this.showError();
            }
        },

        /**
         * show success message
         */
        hideMessages: function() {
            this.$errorMessage.hide();
            this.$successMessage.hide();
            this.$googleErrorMessage.hide();
            this.$googleWarningMessage.hide();
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
        },

        /**
         * show error message from google API
         */
        showGoogleError: function() {
            this.$googleErrorMessage.show();
        },

        /**
         * Remove check status for checkbox
         */
        unCheck: function() {
            this.$el.find('input').prop('checked', false);
        }
    });

    return GoogleSyncCheckboxView;
});
