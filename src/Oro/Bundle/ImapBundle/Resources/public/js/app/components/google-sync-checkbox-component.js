define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var GoogleSyncCheckbox;

    GoogleSyncCheckbox = BaseComponent.extend({
        clientId: null,

        scopes: ['https://www.googleapis.com/auth/gmail.readonly'],

        $errorMessage: null,

        $successMessage: null,

        $googleSyncCheckbox: null,

        $clientIdElement: null,

        initialize: function(options) {
            require(['//apis.google.com/js/client.js?onload=checkAuth']);
            this.$errorMessage = this.findControlElement(options._sourceElement, options.errorMessage);
            this.$successMessage = this.findControlElement(options._sourceElement, options.successMessage);
            this.$googleSyncCheckbox = this.findControlElement(options._sourceElement, "input[type=checkbox]");
            this.$clientIdElement = $('input[id*="client_id"]');
            if (this.$googleSyncCheckbox.length) {
                this.bindEvents();
            }
        },
        bindEvents: function() {
            var self = this;
            this.$googleSyncCheckbox.on('change', function() {
                if ($(this).is(':checked')) {
                    var checkAuthorization = function(result) {
                        if (result && !result.error) {
                            self.showSuccess();
                        } else {
                            self.showError(result);
                        }
                    };

                    gapi.auth.authorize(
                        {
                            'client_id': self.$clientIdElement.val(),
                            'scope': self.scopes.join(' '),
                            'immediate': false
                        }, checkAuthorization);
                } else {
                    self.hideMessages();
                }
            });
        },

        hideMessages: function() {
            this.$errorMessage.hide();
            this.$successMessage.hide();
        },

        showSuccess: function() {
            this.hideMessages();
            this.$successMessage.show();
        },

        showError: function(result) {
            this.hideMessages();
            this.$errorMessage.show();
        },

        /**
         * Looks for the control element relatively from the source element
         *
         * @param {jQuery} $sourceElement
         * @param {string} selector
         * @returns {jQuery}
         */
        findControlElement: function($sourceElement, selector) {
            return $sourceElement.parent().find(selector);
        }
    });

    return GoogleSyncCheckbox;
});
