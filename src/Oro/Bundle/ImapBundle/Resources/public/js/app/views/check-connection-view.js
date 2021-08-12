define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const layout = require('oroui/js/layout');
    const messenger = require('oroui/js/messenger');
    const BaseView = require('oroui/js/app/views/base/view');

    const CheckConnectionView = BaseView.extend({
        SUCCESS_MESSAGE_DELAY: 5000,

        route: 'oro_imap_connection_check',

        entity: 'user',

        entityId: 0,

        organization: '',

        formPrefix: '',

        requestPrefix: 'oro_imap_configuration',

        events: {
            'click [data-role=check-connection-btn]': 'requestAPI',
            'change .critical-field :input': 'clear'
        },

        attributes: {
            'data-layout': 'separate'
        },

        /**
         * @inheritdoc
         */
        constructor: function CheckConnectionView(options) {
            CheckConnectionView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this._setAttributes(this.attributes);
            _.extend(this, _.pick(options, ['entity', 'entityId', 'organization', 'formPrefix']));
            this.model.on('change', this.render, this);
        },

        render: function() {
            const imap = this.model.get('imap');
            const $container = this.ensureContainer();
            if ('folders' in imap) {
                $container.replaceWith(imap.folders);
                layout.initPopover(this.$el.find('.folder-tree'));
            } else {
                $container.empty();
            }
            this.initLayout();
        },

        ensureContainer: function() {
            let $container = this.$el.find('.folder-tree');
            if ($container.length === 0) {
                $container = $('<div/>', {'class': 'control-group folder-tree'});
                this.$el.find('[data-role=check-connection-btn]')
                    .closest('.control-group').parent().append($container);
            }
            return $container;
        },

        requestAPI: function() {
            const data = this.$el.find('.check-connection').serializeArray();
            const $messageContainer = this.$el.find('.check-connection-messages');
            mediator.execute('showLoading');
            this.clear();
            $messageContainer.find('.alert').remove();
            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                data: $.param(this.prepareData(data)),
                success: response => {
                    if (response.imap) {
                        this.showMessage('success', 'oro.imap.connection.imap.success', $messageContainer);
                        this.model.set('imap', response.imap);
                    }
                    if (response.smtp) {
                        this.showMessage('success', 'oro.imap.connection.smtp.success', $messageContainer);
                        this.model.set('smtp', response.smtp);
                    }
                },
                errorHandlerMessage: false,
                error: response => {
                    const responseJSON = response.responseJSON;
                    _.each(responseJSON.errors, function(errorMessage) {
                        messenger.notificationFlashMessage('error', errorMessage, {
                            container: $messageContainer,
                            delay: 0
                        });
                    });
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        },

        showMessage: function(type, message, container) {
            const delay = type === 'error' ? 0 : this.SUCCESS_MESSAGE_DELAY;
            messenger.notificationFlashMessage(type, __(message), {
                container: container,
                delay: delay
            });
        },

        prepareData: function(data) {
            const result = [];
            const start = this.formPrefix.length;
            if (start > 0) {
                _.each(data, item => {
                    if (item.name.indexOf(this.formPrefix) === 0) {
                        item.name = this.requestPrefix + item.name.substr(start);
                        result.push(item);
                    }
                });
                return result;
            } else {
                return data;
            }
        },

        getUrl: function() {
            return routing.generate(this.route, this._getUrlParams());
        },

        _getUrlParams: function() {
            const params = {
                for_entity: this.entity,
                organization: this.organization
            };
            if (this.entityId !== null) {
                params.id = this.entityId;
            }

            return params;
        },

        clear: function() {
            this.model.set({imap: {}, smtp: []});
        }
    });

    return CheckConnectionView;
});
