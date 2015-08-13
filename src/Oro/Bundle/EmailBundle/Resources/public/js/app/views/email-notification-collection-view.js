/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'underscore',
    'oroui/js/mediator',
    'routing',
    'oroui/js/app/views/base/collection-view',
    'oroemail/js/app/views/email-notification-view',
    'oroui/js/messenger'
], function($,
            __,
            _,
            mediator,
            routing,
            BaseCollectionView,
            EmailNotificationView,
            messenger) {
    'use strict';

    var EmailNotificationCollectionView;

    EmailNotificationCollectionView = BaseCollectionView.extend({
        listSelector: '.items',

        countNewEmail: 0,

        events: {
            'click button.mark-as-read': 'onClickMarkAsRead',
            'reset collection': 'onResetCollection'
        },

        initialize: function(options) {
            BaseCollectionView.__super__.initialize.apply(this, options);
            this.countNewEmail = parseInt(options.countNewEmail);

            this.itemView = this.itemView.extend({
                collectionView: this
            });

            this.updateViewMode();
            this.$el.show();
            this.initEvents();
        },

        updateViewMode: function() {
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

        onClickMarkAsRead: function() {
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                success: function(response) {
                    self.collection.markAllAsRead();
                    self.setCount(0);
                    if (response.successful) {
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                },
                error: function(model, response) {
                    messenger.showErrorMessage(__('oro.email.error.mark_as_read'), response.responseJSON || {});
                }
            });
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

        onResetCollection: function() {
            this.setCount(0);
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
            this.updateViewMode();
        },

        initEvents: function() {
            var self = this;

            this.$el.click(function() {
                if (self.isActiveTypeDropDown('notification')) {
                    self.open();
                    self.setModeDropDownMenu('content');
                }
                self.updateViewMode();
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

    return EmailNotificationCollectionView;
});
