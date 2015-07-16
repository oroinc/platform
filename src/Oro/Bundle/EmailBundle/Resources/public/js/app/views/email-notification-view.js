/*global define*/
define([
    'jquery',
    'oroemail/js/app/models/email-attachment-model',
    'oroui/js/app/views/base/view'
], function ($, EmailAttachmentModel, BaseView) {
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
            for (var i in this.collection.models ) {
                var view = this.template({
                    entity: this.collection.models[i]
                });

                var $view = $(view);
                this.$containerContextTargets.append($view);
            }
        },

        onClickMarkAsRead: function () {

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
