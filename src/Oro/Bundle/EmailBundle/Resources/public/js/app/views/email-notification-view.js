/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'underscore',
    'oroui/js/mediator',
    'routing',
    'oroui/js/app/views/base/view',
    'oroui/js/messenger',
    'oroemail/js/app/models/email-notification-model'
], function($, __, _, mediator, routing, BaseView, messenger, EmailNotificationModel) {
    'use strict';

    var EmailNotificationView;

    EmailNotificationView = BaseView.extend({
        model: EmailNotificationModel,

        collectionView: null,

        templateSelector: '#email-notification-item-template',

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
        getTemplateData: function() {
            return {
                'entity': this.model
            };
        },

        onClickOpenEmail: function() {
            var url = routing.generate('oro_email_thread_view', {id: this.model.get('id')});
            this.model.set({'seen': true});
            this.collectionView.updateViewMode();
            mediator.execute('redirectTo', {url: url});
        }
    });

    return EmailNotificationView;
});
