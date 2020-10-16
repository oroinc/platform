define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const popup = require('oroimap/js/app/components/popup');
    const routing = require('routing');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const MicrosoftSyncCheckboxView = require('oroimap/js/app/views/microsoft-sync-checkbox-view');
    const $ = require('jquery');

    const MicrosoftSyncCheckbox = BaseComponent.extend({
        /** @property {jQuery} */
        $clientIdElement: null,

        /** @property {jQuery} */
        $tenantElement: null,

        /** @property {jQuery} */
        $secretElement: null,

        /** @property {Array|String[]} */
        scopes: [
            'openid',
            'offline_access',
            'User.Read',
            'profile',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/POP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send'
        ],

        /**
         * @inheritDoc
         */
        constructor: function MicrosoftSyncCheckbox(options) {
            MicrosoftSyncCheckbox.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.routeAccessToken = options.routeAccessToken;
            this.$clientIdElement = options._sourceElement
                .closest('form[name="microsoft_settings"]')
                .find('input[id*="client_id"]');
            this.$tenantElement = options._sourceElement
                .closest('form[name="microsoft_settings"]')
                .find('input[id*="tenant"]');
            this.$secretElement = options._sourceElement
                .closest('form[name="microsoft_settings"]')
                .find('input[id*="client_secret"]');

            this.view = new MicrosoftSyncCheckboxView({
                el: options._sourceElement,
                errorMessage: options.errorMessage,
                successMessage: options.successMessage,
                vendorErrorMessage: options.vendorErrorMessage,
                vendorWarningMessage: options.vendorWarningMessage
            });

            this.addDisableEventListeners(options);

            this.listenTo(this.view, 'requestToken', this.requestToken);
        },

        /**
         * Applies disable events on each change of the settings
         *
         * @param {Object} options
         */
        addDisableEventListeners: function(options) {
            $('form[name="microsoft_settings"] :input[id*="microsoft_settings_oro_microsoft_integration"]')
                .on('change.microsoft_enable_oauth', function(ev) {
                    options._sourceElement.find('input[type=checkbox]').prop('disabled', true);
                });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            $('form[name="microsoft_settings"] :input[id*="microsoft_settings_oro_microsoft_integration"]')
                .off('change.microsoft_enable_oauth');

            return MicrosoftSyncCheckbox.__super__.dispose.call(this);
        },

        /**
         * @return {string}
         */
        buildLoginUrl: function() {
            const data = (function(tenant, clientId, secret) {
                const errors = [];
                if ($.trim(tenant).length === 0) {
                    errors.push(__('oro.imap.connection.microsoft.oauth.error.emptyTenant'));
                }
                if ($.trim(clientId).length === 0) {
                    errors.push(__('oro.imap.connection.microsoft.oauth.error.emptyClientId'));
                }
                if ($.trim(secret).length === 0) {
                    errors.push(__('oro.imap.connection.microsoft.oauth.error.emptySecret'));
                }

                if (errors.length) {
                    throw new Error(errors.join('<br />'));
                }

                return {
                    tenant: tenant,
                    clientId: clientId,
                    secret: secret
                };
            })(this.$tenantElement.val(), this.$clientIdElement.val(), this.$secretElement.val());
            const url = 'https://login.microsoftonline.com/' + data.tenant + '/oauth2/v2.0/authorize';
            const urlParts = {
                client_id: data.clientId,
                response_type: 'code',
                redirect_uri: routing.generate('oro_imap_microsoft_access_token', {}, true),
                response_mode: 'query',
                scope: this.scopes.join(' '),
                state: Math.floor(10000 + Math.random() * 90000)
            };

            return [url, $.param(urlParts)].join('?');
        },

        requestToken: function() {
            try {
                this.doRequestToken();
            } catch (err) {
                this.view.setVendorErrorMessage(err.message);
                this.view.render();
            }
        },

        doRequestToken: function() {
            popup.whenAuthorized(this.buildLoginUrl())
                .done(function() {
                    this
                        .whenRquestAccessToken()
                        .done(function(result) {
                            this.view.setToken(result);
                            this.view.render();
                        }.bind(this))
                        .fail(function() {
                            this.view.setVendorErrorMessage(__('oro.imap.connection.microsoft.oauth.error.token'));
                            this.view.render();
                        }.bind(this));
                }.bind(this))
                .fail(function() {
                    this.view.setVendorErrorMessage(__('oro.imap.connection.microsoft.oauth.error.token'));
                    this.view.render();
                }.bind(this));
        },

        /**
         * Provides access token promise
         *
         * @return {Promise}
         */
        whenRquestAccessToken: function() {
            return $.ajax({
                url: routing.generate(this.routeAccessToken),
                method: 'POST',
                data: {}
            });
        }
    });

    return MicrosoftSyncCheckbox;
});
