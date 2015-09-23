define(function(require) {
    'use strict';

    var CheckConnectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var messenger = require('oroui/js/messenger');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckConnectionView = BaseView.extend({
        formPrefix: '',
        requestPrefix: 'oro_imap_configuration',
        events: {
            'click [data-role=check-connection-btn]': 'requestAPI',
            'change .critical-field :input': 'clear'
        },
        initialize: function(options) {
            _.extend(this, _.pick(options, ['params', 'url', 'formPrefix']));
            this.model.on('change', this.render, this);
        },

        render: function() {
            var imap = this.model.get('imap');
            var $container = this.$el.find('.folder-tree');
            if ('folders' in imap) {
                $container.replaceWith(imap.folders);
                layout.initPopover(this.$el.find('.folder-tree'));
            } else {
                $container.empty();
            }
        },

        requestAPI: function() {
            var data = this.$el.find('.check-connection').serializeArray();
            var $messageContainer = this.$el.find('.check-connection-messages');
            mediator.execute('showLoading');
            this.model.set({'imap': {}, 'smtp': []});
            this.model.sync('create', this.model, {
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
                    mediator.execute('hideLoading');
                }, this),
                error: _.bind(function() {
                    this.showMessage('error', 'oro.imap.connection.error', $messageContainer);
                    mediator.execute('hideLoading');
                }, this)
            });
        },

        showMessage: function(type, message, container) {
            messenger.notificationFlashMessage(type, __(message), {
                container: container,
                delay: 5000
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
        }
    });

    return CheckConnectionView;
});
