define(function(require) {
    'use strict';

    var EmailNotificationView;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationView = BaseView.extend({
        tagName: 'li',

        templateSelector: '#email-notification-item-template',

        className: function() {
            return this.model.get('seen') ? '' : 'new';
        },

        events: {
            'click .info': 'onClickOpenEmail'
        },

        listen: {
            'change model': 'render'
        },

        render: function() {
            EmailNotificationView.__super__.render.apply(this, arguments);
            this.$el.find('.replay a').attr('data-url', this.model.get('route'));
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
        }
    });

    return EmailNotificationView;
});
