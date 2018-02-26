define(function(require) {
    'use strict';

    var CheckConnectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var messenger = require('oroui/js/messenger');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckConnectionView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function CheckConnectionView() {
            CheckConnectionView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this._setAttributes(this.attributes);
            _.extend(this, _.pick(options, ['entity', 'entityId', 'organization', 'formPrefix']));
            this.model.on('change', this.render, this);
        },

        render: function() {
            var imap = this.model.get('imap');
            var $container = this.ensureContainer();
            if ('folders' in imap) {
                $container.replaceWith(imap.folders);
                layout.initPopover(this.$el.find('.folder-tree'));
            } else {
                $container.empty();
            }
            this.initLayout();
        },

        ensureContainer: function() {
            var $container = this.$el.find('.folder-tree');
            if ($container.length === 0) {
                $container = $('<div/>', {'class': 'control-group folder-tree'});
                this.$el.find('[data-role=check-connection-btn]')
                    .closest('.control-group').parent().append($container);
            }
            return $container;
        },

        requestAPI: function() {
            var data = this.$el.find('.check-connection').serializeArray();
            var $messageContainer = this.$el.find('.check-connection-messages');
            mediator.execute('showLoading');
            this.clear();
            $messageContainer.find('.alert').remove();
            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                data: $.param(this.prepareData(data)),
                success: _.bind(function(response) {
                    if (response.imap) {
                        if (response.imap.error) {
                            this.showMessage('error', 'oro.imap.connection.imap.error', $messageContainer);
                        } else {
                            this.showMessage('success', 'oro.imap.connection.imap.success', $messageContainer);
                            this.model.set('imap', response.imap);
                        }
                    }
                    if (response.smtp) {
                        if (response.smtp.error) {
                            this.showMessage('error', 'oro.imap.connection.smtp.error', $messageContainer);
                        } else {
                            this.showMessage('success', 'oro.imap.connection.smtp.success', $messageContainer);
                            this.model.set('smtp', response.smtp);
                        }
                    }
                }, this),
                errorHandlerMessage: false,
                error: _.bind(function() {
                    this.showMessage('error', 'oro.imap.connection.error', $messageContainer);
                }, this),
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        },

        showMessage: function(type, message, container) {
            var delay = type === 'error' ? 0 : this.SUCCESS_MESSAGE_DELAY;
            messenger.notificationFlashMessage(type, __(message), {
                container: container,
                delay: delay
            });
        },

        prepareData: function(data) {
            var result = [];
            var start = this.formPrefix.length;
            if (start > 0) {
                _.each(data, _.bind(function(item) {
                    if (item.name.indexOf(this.formPrefix) === 0) {
                        item.name = this.requestPrefix + item.name.substr(start);
                        result.push(item);
                    }
                }, this));
                return result;
            } else {
                return data;
            }
        },

        getUrl: function() {
            return routing.generate(this.route, this._getUrlParams());
        },

        _getUrlParams: function() {
            var params = {
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
