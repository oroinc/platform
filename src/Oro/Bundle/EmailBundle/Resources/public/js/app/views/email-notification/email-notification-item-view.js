define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Backbone = require('backbone');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailNotificationView = BaseView.extend({
        tagName: 'li',

        attributes: {
            'data-layout': 'separate'
        },

        templateSelector: '#email-notification-item-template',

        events: {
            'click .title': 'onClickOpenEmail',
            'click [data-role=toggle-read-status]': 'onClickReadStatus'
        },

        listen: {
            'change model': 'render',
            'addedToParent': 'delegateEvents'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationView(options) {
            EmailNotificationView.__super__.constructor.call(this, options);
        },

        render: function() {
            EmailNotificationView.__super__.render.call(this);
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
            const url = routing.generate('oro_email_thread_view', {id: this.model.get('id')});
            this.model.set({seen: true});
            mediator.execute('redirectTo', {url: url});
        },

        onClickReadStatus: function(e) {
            e.stopPropagation();
            const model = this.model;
            const status = model.get('seen');
            const url = routing.generate('oro_email_mark_seen', {
                id: model.get('id'),
                status: status ? 0 : 1,
                checkThread: 0
            });
            model.set('seen', !status);
            Backbone.ajax({
                type: 'POST',
                url: url,
                success: function(response) {
                    if (_.result(response, 'successful') !== true) {
                        model.set('seen', status);
                        mediator.execute('showErrorMessage', __('Sorry, an unexpected error has occurred.'), 'error');
                    }
                },
                error: function(xhr, err, message) {
                    model.set('seen', status);
                }
            });
        }
    });

    return EmailNotificationView;
});
