define(function(require) {
    'use strict';

    const $ = require('jquery');

    const BaseComponent = require('oroui/js/app/components/base/component');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const messenger = require('oroui/js/messenger');
    const systemAccessModeOrganizationProvider = require('oroorganization/js/app/tools/system-access-mode-organization-provider');

    const IntegrationConnectionCheckerComponent = BaseComponent.extend({
        /**
         * @property {jquery} $button
         */
        $button: null,

        /**
         * @property {jquery} $form
         */
        $form: null,

        /**
         * @property {string} backendUrl
         */
        backendUrl: '',

        /**
         * @property {LoadingMaskView} loadingMaskView
         */
        loadingMaskView: null,

        /**
         * @inheritdoc
         */
        constructor: function IntegrationConnectionCheckerComponent(options) {
            IntegrationConnectionCheckerComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$button = options._sourceElement;
            this.$form = $(options.formSelector);
            this.backendUrl = options.backendUrl;
            this.loadingMaskView = new LoadingMaskView({container: $('body')});

            this.initListeners();
        },

        initListeners: function() {
            this.$button.on('click', this.buttonClickHandler.bind(this));
        },

        buttonClickHandler: function() {
            this.$form.validate();

            if (this.$form.valid()) {
                this.checkConnection();
            }
        },

        checkConnection: function() {
            const self = this;
            let data = this.$form.serialize();

            const organizationId = systemAccessModeOrganizationProvider.getOrganizationId();

            if (organizationId) {
                data += '&_sa_org_id=' + organizationId;
            }

            $.ajax({
                url: this.backendUrl,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    self.loadingMaskView.show();
                },
                success: this.successHandler.bind(this),
                complete: function() {
                    self.loadingMaskView.hide();
                }
            });
        },

        /**
         * @param {{success: bool, message: string}} response
         */
        successHandler: function(response) {
            let type = 'error';
            if (response.success) {
                type = 'success';
            }

            messenger.notificationFlashMessage(type, response.message);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off('click');

            IntegrationConnectionCheckerComponent.__super__.dispose.call(this);
        }
    });

    return IntegrationConnectionCheckerComponent;
});
