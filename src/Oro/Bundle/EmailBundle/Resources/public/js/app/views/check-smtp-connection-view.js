define(function(require) {
    'use strict';

    var CheckConnectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckConnectionView = BaseView.extend({
        route: 'oro_email_check_smtp_connection',

        entity: 'user',

        entityId: 0,

        organization: '',

        requestPrefix: 'oro_email_configuration',

        events: {
            'click [data-role=check-smtp-connection]': 'checkSmtpConnection'
        },

        /**
         * @inheritDoc
         */
        constructor: function CheckConnectionView() {
            CheckConnectionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['entity', 'entityId', 'organization']));
        },

        checkSmtpConnection: function(event) {
            var data = this.$el.find('[data-class="smtp_settings"]').serializeArray();
            var $messageContainer = this.$el.find('.check-smtp-connection-messages');
            mediator.execute('showLoading');
            this.clear();
            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                data: this.prepareData(data),
                success: _.bind(function(response) {
                    if (response) {
                        this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                    } else {
                        this.showMessage('success', 'oro.email.smtp_connection.success', $messageContainer);
                    }
                }, this),
                errorHandlerMessage: false,
                error: _.bind(function() {
                    this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                }, this),
                complete: function() {
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
            var result = {};
            _.each(data, _.bind(function(item) {
                var pattern = /^([^\[]+\[.+_)([^\]]+)(\].*)/i;
                if (pattern.test(item.name)) {
                    item.name = item.name.match(pattern)[2];
                    result[item.name] = item.value;
                }
            }, this));
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
