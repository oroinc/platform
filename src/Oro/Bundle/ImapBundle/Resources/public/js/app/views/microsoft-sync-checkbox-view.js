define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const MicrosoftSyncCheckboxView = BaseView.extend({

        /** @property {jQuery|null} */
        $errorMessage: null,

        /** @property {jQuery|null} */
        $successMessage: null,

        /** @property {jQuery|null} */
        $vendorErrorMessage: null,

        /** @property {jQuery|null} */
        $vendorWarningMessage: null,

        /** @property {String|null} */
        token: null,

        /** @property {String} */
        vendorErrorMessage: '',

        /** @property {Boolean} */
        canShowMessage: false,

        /** @property {Object} */
        events: {
            'change input[type=checkbox]': 'onChangeCheckBox'
        },

        /** @property {Object} */
        listen: {
            'change:canShowMessage': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function MicrosoftSyncCheckboxView(options) {
            MicrosoftSyncCheckboxView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$errorMessage = this.$el.find(options.errorMessage);
            this.$successMessage = this.$el.find(options.successMessage);
            this.$vendorErrorMessage = this.$el.find(options.vendorErrorMessage);
            this.$vendorWarningMessage = this.$el.find(options.vendorWarningMessage);
        },

        render: function() {
            this.$vendorErrorMessage.html(this.vendorErrorMessage);

            if (this.canShowMessage) {
                this.showMessage();
            } else {
                this.hideMessages();
            }
        },

        /**
         * Set response from oAuth2.0 authentication
         *
         * @param {String} token
         * @return {this}
         */
        setToken: function(token) {
            this.token = token;
            return this;
        },

        /**
         * Set error message from API
         *
         * @params {String} message
         * @return {this}
         */
        setVendorErrorMessage: function(message) {
            this.vendorErrorMessage = message;
            return this;
        },

        /**
         * Reset error message from API
         *
         * @return {this}
         */
        resetVendorErrorMessage: function() {
            this.vendorErrorMessage = '';
            return this;
        },

        /**
         * Reset property token
         *
         * @return {this}
         */
        resetToken: function() {
            this.token = null;
            return this;
        },

        /**
         * Handler event change for checkbox
         *
         * @param {Object} event
         */
        onChangeCheckBox: function(event) {
            this.resetVendorErrorMessage();
            this.resetToken();
            this.hideMessages();

            if ($(event.target).is(':checked')) {
                this.canShowMessage = true;
                this.trigger('requestToken');
            } else {
                this.canShowMessage = false;
                this.$vendorWarningMessage.show();
            }
        },

        /**
         * Show success or error message
         */
        showMessage: function() {
            this.hideMessages();

            if (this.vendorErrorMessage.length > 0) {
                this.unCheck();
                this.showVendorError();
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
            this.$vendorErrorMessage.hide();
            this.$vendorWarningMessage.hide();
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
         * show error message from API
         */
        showVendorError: function() {
            this.$vendorErrorMessage.show();
        },

        /**
         * Remove check status for checkbox
         */
        unCheck: function() {
            this.$el.find('input').prop('checked', false);
        }
    });

    return MicrosoftSyncCheckboxView;
});
