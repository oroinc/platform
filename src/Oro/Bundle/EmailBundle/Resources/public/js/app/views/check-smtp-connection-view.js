define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const BaseView = require('oroui/js/app/views/base/view');

    const CheckConnectionView = BaseView.extend({
        route: 'oro_email_check_smtp_connection',

        entity: 'user',

        entityId: 0,

        organization: '',

        requestPrefix: 'oro_email_configuration',

        events: {
            'click [data-role=check-smtp-connection]': 'checkSmtpConnection'
        },

        /**
         * @inheritdoc
         */
        constructor: function CheckConnectionView(options) {
            CheckConnectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['entity', 'entityId', 'organization']));
        },

        checkSmtpConnection: function(event) {
            const data = this.$el.find('[data-class="smtp_settings"]').serializeArray();
            const $messageContainer = this.$el.find('.check-smtp-connection-messages');
            mediator.execute('showLoading');
            this.clear();
            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                data: this.prepareData(data),
                success: response => {
                    if (response) {
                        this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                    } else {
                        this.showMessage('success', 'oro.email.smtp_connection.success', $messageContainer);
                    }
                },
                errorHandlerMessage: false,
                error: () => {
                    this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                },
                complete: () => {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        },

        showMessage: function(type, message, container) {
            messenger.notificationFlashMessage(type, __(message), {
                container: container,
                delay: 5000
            });
        },

        prepareData: function(data) {
            const result = {};
            _.each(data, item => {
                const pattern = /^([^\[]+\[.+_)([^\]]+)(\].*)/i;
                if (pattern.test(item.name)) {
                    item.name = item.name.match(pattern)[2];
                    result[item.name] = item.value;
                }
            });
            return result;
        },

        getUrl: function() {
            return routing.generate(this.route);
        },

        clear: function() {
            // set model data to default
            this.model.set({
                host: '',
                port: null,
                encryption: null,
                username: '',
                password: ''
            });
        }
    });

    return CheckConnectionView;
});
