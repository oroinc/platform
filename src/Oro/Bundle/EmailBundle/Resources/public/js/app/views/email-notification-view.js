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
        countNewEmail:null,
        inputName: '',
        events: {
            'click a.mark-as-read': 'onClickMarkAsRead',
            'click .info': 'onClickOpenEmail'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.template = _.template($('#email-notification-item').html());
            this.$containerContextTargets = $(options.el).find('.items');
            this.countNewEmail = this.getCount();
            this.$el.show();
            this.initCollection().initEvents();

            var self = this;
            this.$el.click(function() {
                if (self.isActiveTypeDropDown('notification')) {
                    self.$el.addClass('open');
                    self.setTypeDropDownMenu('content');
                }
                self.initViewType();
            });
        },

        initCollection: function () {
            var emails = this.getInitData();
            this.collection = new EmailNotificationCollection(emails);

            return this;
        },

        render: function () {
            var $view;
            this.$containerContextTargets.empty();
            this.initViewType();

            for (var i in this.collection.models) {
                $view = this.getView(this.collection.models[i]);
                this.$containerContextTargets.append($view);
            }
        },

        getView: function (model) {
            var view = this.template({
                entity: model
            });
            var $view = $(view);
            $view.find('.replay a').attr('data-url', model.get('route'));

            if (model.get('seen')) {
                $view.removeClass('new');
                $view.find('.icon-envelope').removeClass('new');
            }

            return $view;
        },

        onClickMarkAsRead: function () {
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                success: function () {
                    self.collection.markAllAsRead();
                    self.render();
                    self.onChangeAmount(0);
                    mediator.trigger('datagrid:doRefresh:user-email-grid');
                }
            })
        },

        getClankEvent: function () {
            return $(this.el).data('clank-event');
        },

        getInitData: function () {
            return $(this.el).data('emails');
        },

        getCount: function () {
            return $(this.el).data('count');
        },
        initViewType: function () {
            if (!this.isActiveTypeDropDown('notification')) {
                if (this.collection.models.length === 0) {
                    this.setTypeDropDownMenu('empty');
                    this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                } else {
                    this.setTypeDropDownMenu('content');
                    if (this.countNewEmail > 0) {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').addClass('new');
                    } else {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                    }
                }
            }
        },

        resetTypeDropDownMenu: function() {
            this.$el.find('.dropdown-menu').removeClass('content empty notification');

            return this;
        },
        setTypeDropDownMenu:function(type) {
            this.resetTypeDropDownMenu();
            this.$el.find('.dropdown-menu').addClass(type);
        },

        isActiveTypeDropDown: function(type) {
            return this.$el.find('.dropdown-menu').hasClass(type);
        },

        onClickOpenEmail: function (e) {
            var id  = $(e.currentTarget).data('id');
            mediator.execute(
                'redirectTo',
                {
                    url: routing.generate('oro_email_view', {id: id})
                }
            );
            var model = this.collection.find(function(item){
                return Number(item.get('id')) === id;
            });

            this.$el.find('#'+model.cid).removeClass('new');
            this.$el.find('#'+model.cid).find('.icon-envelope').removeClass('new');
            this.initViewType();
        },

        onChangeAmount: function (count) {
            this.countNewEmail = count;
            if (count > 10) {
                count = '10+';
            }

            if (count == 0) {
                count = '';
            }
            this.$el.find('.icon-envelope span').html(count);
            this.initViewType();
        },

        initEvents: function () {
            var self = this;

            this.collection.on('reset', function () {
                self.$containerContextTargets.html('');
                self.onChangeAmount(0);
            });

            this.collection.on('add', function (model) {
                var $view = self.getView(model);
                self.$containerContextTargets.append($view);
                self.initLayout();
            });
        },

        showNotification: function() {
            if (!this.$el.hasClass('open')) {
                this.$el.addClass('open');
                this.setTypeDropDownMenu('notification');
            }
        }
    });

    return EmailAttachmentView;
});
