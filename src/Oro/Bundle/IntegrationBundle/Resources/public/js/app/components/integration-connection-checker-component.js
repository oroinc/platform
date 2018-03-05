define(function(require) {
    'use strict';

    var $ = require('jquery');

    var IntegrationConnectionCheckerComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var messenger = require('oroui/js/messenger');

    IntegrationConnectionCheckerComponent = BaseComponent.extend({
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
         * @inheritDoc
         */
        constructor: function IntegrationConnectionCheckerComponent() {
            IntegrationConnectionCheckerComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
            var self = this;

            $.ajax({
                url: this.backendUrl,
                type: 'POST',
                data: this.$form.serialize(),
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
            var type = 'error';
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
