define(function(require) {
    'use strict';

    var EmailNotificationView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Backbone = require('backbone');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationView = BaseView.extend({
        tagName: 'li',

        templateSelector: '#email-notification-item-template',

        events: {
            'click .title': 'onClickOpenEmail',
            'click [data-role=toggle-read-status]': 'onClickReadStatus'
        },

        listen: {
            'change model': 'render',
            'addedToParent': 'delegateEvents'
        },

        render: function() {
            EmailNotificationView.__super__.render.apply(this, arguments);
            this.$el.toggleClass('highlight', !this.model.get('seen'));
            this.initLayout();
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $(this.templateSelector).html();
            }

            return EmailNotificationView.__super__.getTemplateFunction.call(this);
        },

        onClickOpenEmail: function() {
            var url = routing.generate('oro_email_thread_view', {id: this.model.get('id')});
            this.model.set({'seen': true});
            mediator.execute('redirectTo', {url: url});
        },

        onClickReadStatus: function(e) {
            e.stopPropagation();
            var model = this.model;
            var status = model.get('seen');
            var url = routing.generate('oro_email_mark_seen', {
                id: model.get('id'),
                status: status ? 0 : 1,
                checkThread: 0
            });
            model.set('seen', !status);
            Backbone.ajax({
                type: 'GET',
                url: url,
                success: function(response) {
                    if (_.result(response, 'successful') !== true) {
                        model.set('seen', status);
                        mediator.execute('showErrorMessage', __('Sorry, unexpected error was occurred'), 'error');
                    }
                },
                error: function(xhr, err, message) {
                    model.set('seen', status);
                    mediator.execute('showErrorMessage', message, err);
                }
            });
        }
    });

    return EmailNotificationView;
});
