/*global define*/
define([
    'jquery',
    'oroemail/js/app/models/email-attachment-model',
    'oroui/js/app/views/base/view',
    'oroemail/js/app/models/email-notification-collection',
    'routing',
    'oroui/js/mediator'
], function ($, EmailAttachmentModel, BaseView, EmailNotificationCollection, routing, mediator) {
    'use strict';

    var EmailAttachmentView;

    EmailAttachmentView = BaseView.extend({
        contextsView: null,
        inputName: '',
        events: {
            'click a.mark-as-read': 'onClickMarkAsRead',
            'click .info': 'onClickOpenEmail',
            'click .replay': 'onClickReplay'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.template = _.template($('#email-notification-item').html());
            this.$containerContextTargets = $(options.el).find('.items');

            this.initCollection().initEvents();
        },

        initCollection: function () {
            var emails = this.getInitData();
            this.collection = new EmailNotificationCollection(emails);

            return this;
        },

        render:function () {
            this.$containerContextTargets.empty();
            this.initViewType();

            for (var i in this.collection.models ) {
                var view = this.template({
                    entity: this.collection.models[i]
                });
                var $view = $(view);
                $view.find('.replay a').attr('data-url', this.collection.models[i].get('route'));
                this.$containerContextTargets.append($view);
            }
        },

        onClickMarkAsRead: function () {
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                success: function() {
                    self.collection.reset();
                }
            })
        },

        getClankEvent: function () {
            return $(this.el).data('clank-event');
        },

        getInitData: function () {
            return $(this.el).data('emails');
        },

        initViewType: function() {
            if (this.collection.models.length === 0) {
                this.$el.find('.content').hide();
                this.$el.find('.empty').show();
                this.$el.find('.icon-envelope').removeClass('new');
            } else {
                this.$el.find('.content').show();
                this.$el.find('.empty').hide();
                this.$el.find('.icon-envelope').addClass('new');
            }
        },

        onClickOpenEmail:function (e)
        {
            mediator.execute(
                'redirectTo',
                {
                    url: routing.generate('oro_email_view', {id: $(e.currentTarget).data('id')})
                }
            );
        },

        onClickReplay: function(e) {

        },

        onChangeAmount: function(count) {
            if (count > 10 ){
                count = '10+';
            }

            this.$el.find('.icon-envelope span').html(count);
            this.initViewType();
        },

        initEvents: function() {
            var self = this;

            this.collection.on('reset', function() {
                self.$containerContextTargets.html('');
                self.onChangeAmount(0);
            });

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model
                });

                var $view = $(view);
                $view.find('.replay a').attr('data-url', model.get('route'));
                self.$containerContextTargets.prepend($view);
            });
        }
    });

    return EmailAttachmentView;
});
