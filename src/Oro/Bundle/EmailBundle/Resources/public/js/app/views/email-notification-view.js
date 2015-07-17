/*global define*/
define([
    'jquery',
    'oroemail/js/app/models/email-attachment-model',
    'oroui/js/app/views/base/view',
    'routing'
], function ($, EmailAttachmentModel, BaseView, routing) {
    'use strict';

    var EmailAttachmentView;

    EmailAttachmentView = BaseView.extend({
        contextsView: null,
        inputName: '',
        events: {
            'click a.mark-as-read': 'onClickMarkAsRead'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.template = _.template($('#email-notification-item').html());
            this.$containerContextTargets = $(options.el).find('.items');
        },

        render:function () {
            this.$containerContextTargets.empty();
            if (this.collection.models.length === 0) {
                this.$el.find('.content').hide();
                this.$el.find('.empty').show();
            } else {
                this.$el.find('.content').show();
                this.$el.find('.empty').hide();
            }

            for (var i in this.collection.models ) {
                var view = this.template({
                    entity: this.collection.models[i]
                });

                var $view = $(view);
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

        getEmails: function () {
            return $(this.el).data('emails');
        },

        setCollection:function(collection)
        {
            this.collection = collection;
        },

        initEvents: function() {
            var self = this;

            //this.collection.on('reset', function() {
            //    self.$containerContextTargets.html('');
            //});

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model
                });

                var $view = $(view);
                self.$containerContextTargets.prepend($view);
            });
        }
    });

    return EmailAttachmentView;
});
