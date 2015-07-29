/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'underscore',
    'oroui/js/mediator',
    'routing',
    'oroui/js/app/views/base/view',
    'oroemail/js/app/models/email-notification-collection',
    'oroui/js/messenger'
], function($, __, _, mediator, routing, BaseView, EmailNotificationCollection, messenger) {
    'use strict';

    var EmailNotificationView;

    EmailNotificationView = BaseView.extend({
        contextsView: null,
        countNewEmail: null,
        inputName: '',
        events: {
            'click button.mark-as-read': 'onClickMarkAsRead',
            'click .info': 'onClickOpenEmail'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.template = _.template($('#email-notification-item').html());
            this.$containerEmails = $(options.el).find('.items');
            this.countNewEmail = this.getDefaultCount();
            this.$el.show();
            this.initCollection().initEvents();
        },

        initCollection: function() {
            var emails = this.getDefaultData();
            this.collection = new EmailNotificationCollection(emails);

            return this;
        },

        render: function() {
            var $view;
            var i;

            this.$containerEmails.empty();
            this.initViewMode();

            for (i in this.collection.models) {
                if (this.collection.models.hasOwnProperty(i)) {
                    $view = this.getView(this.collection.models[i]);
                    this.$containerEmails.append($view);
                }
            }
        },

        getView: function(model) {
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

        onClickMarkAsRead: function() {
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                success: function(response) {
                    self.collection.markAllAsRead();
                    self.render();
                    self.setCount(0);
                    if (response.successful) {
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                    self.initLayout();
                },
                error: function(model, response) {
                    messenger.showErrorMessage(__('oro.email.error.mark_as_read'), response.responseJSON || {});
                }
            });
        },

        getClankEvent: function() {
            return $(this.el).data('clank-event');
        },

        getDefaultData: function() {
            return $(this.el).data('emails');
        },

        getDefaultCount: function() {
            return $(this.el).data('count');
        },

        initViewMode: function() {
            if (!this.isActiveTypeDropDown('notification')) {
                if (this.collection.models.length === 0) {
                    this.setModeDropDownMenu('empty');
                    this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                } else {
                    this.setModeDropDownMenu('content');
                    if (this.countNewEmail > 0) {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').addClass('new');
                    } else {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                    }
                }
            }
        },

        resetModeDropDownMenu: function() {
            this.$el.find('.dropdown-menu').removeClass('content empty notification');

            return this;
        },
        setModeDropDownMenu: function(type) {
            this.resetModeDropDownMenu();
            this.$el.find('.dropdown-menu').addClass(type);
        },

        isActiveTypeDropDown: function(type) {
            return this.$el.find('.dropdown-menu').hasClass(type);
        },

        onClickOpenEmail: function(e) {
            var id  = $(e.currentTarget).data('id');
            var isthread  = $(e.currentTarget).data('isthread');
            var url;
            var model;

            url =  routing.generate('oro_email_thread_view', {id: id});
            mediator.execute('redirectTo', {url: url});
            model = this.collection.indWhere({id: id});

            this.$el.find('#' + model.cid).removeClass('new');
            this.$el.find('#' + model.cid).find('.icon-envelope').removeClass('new');
            this.initViewMode();
        },

        setCount: function(count) {
            count = parseInt(count);
            this.countNewEmail = count;
            if (count > 10) {
                count = '10+';
            }

            if (count === 0) {
                count = '';
            }
            this.$el.find('.icon-envelope span').html(count);
            this.initViewMode();
        },

        initEvents: function() {
            var self = this;

            this.$el.click(function() {
                if (self.isActiveTypeDropDown('notification')) {
                    self.open();
                    self.setModeDropDownMenu('content');
                }
                self.initViewMode();
            });

            this.collection.on('reset', function() {
                self.$containerEmails.empty();
                self.setCount(0);
            });

            this.collection.on('add', function(model) {
                var $view = self.getView(model);
                self.$containerEmails.append($view);
                self.initLayout();
            });
        },

        showNotification: function() {
            if (!this.isOpen()) {
                this.open();
                this.setModeDropDownMenu('notification');
            }
        },

        isOpen: function() {
            this.$el.hasClass('open');
        },

        close: function() {
            this.$el.removeClass('open');
        },

        open: function() {
            this.$el.addClass('open');
        }
    });

    return EmailNotificationView;
});
