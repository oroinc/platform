/*global define*/
define(function(require) {
    'use strict';

    var EmailNotificationCollectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var EmailNotificationView = require('./email-notification-item-view');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var messenger = require('oroui/js/messenger');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');

    EmailNotificationCollectionView = BaseCollectionView.extend({
        template: require('tpl!oroemail/templates/email-notification/email-notification-collection-view.html'),
        itemView: EmailNotificationView,
        animationDuration: 0,
        listSelector: '.items',
        countNewEmail: 0,
        folderId: 0,
        isSidebarWidget: false,
        loadingMask: null,
        /**
         * Id of default action
         *  1 - reply all
         *  2 - reply
         *  3 - forward
         */
        defaultActionId: 1,

        listen: {
            'change:seen collection': 'onSeenStatusChange',
            'reset collection': 'onResetCollection',
            'request collection': 'onCollectionRequest',
            'sync collection': 'onCollectionSync'
        },

        events: {
            'click': 'onClickIconEnvelope',
            'click button.mark-as-read': 'onClickMarkAsRead'
        },

        initialize: function(options) {
            EmailNotificationCollectionView.__super__.initialize.call(this, options);
            this.countNewEmail = parseInt(options.countNewEmail);
            this.folderId = options.folderId;
            this.isSidebarWidget = options.isSidebarWidget;
            if (options.defaultActionId) {
                this.defaultActionId = parseInt(options.defaultActionId);
            }
        },

        render: function() {
            EmailNotificationCollectionView.__super__.render.call(this);
            this.updateViewMode();
        },

        getTemplateData: function() {
            var data = EmailNotificationCollectionView.__super__.getTemplateData.call(this);
            var visibleUnreadEmails = this.collection.filter(function(item) {
                return item.get('seen') === false;
            }).length;
            _.extend(data, _.pick(this, ['defaultActionId', 'countNewEmail', 'folderId', 'isSidebarWidget', 'length']));
            data.userEmailsUrl = routing.generate('oro_email_user_emails');
            data.moreUnreadEmails = Math.max(this.countNewEmail - visibleUnreadEmails, 0);
            return data;
        },

        updateViewMode: function() {
            if (!this.isActiveTypeDropDown('notification')) {
                var $iconEnvelope = this.$el.find('.oro-dropdown-toggle .icon-envelope');
                if (this.collection.models.length === 0) {
                    this.setModeDropDownMenu('empty');
                    $iconEnvelope.removeClass('highlight');
                } else {
                    this.setModeDropDownMenu('content');
                    if (this.countNewEmail > 0) {
                        $iconEnvelope.addClass('highlight');
                    } else {
                        $iconEnvelope.removeClass('highlight');
                    }
                }
            }
        },

        onClickMarkAsRead: function() {
            var ids = [];
            var self = this;
            this.collection.each(function(email) {
                if (email.get('seen') === false) {
                    ids.push(email.get('id'));
                }
            });
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                data: $.param({'ids': ids}),
                success: function(response) {
                    self.collection.markAllAsRead();
                    self.collection.unreadEmailsCount = 0;
                    self.countNewEmail = 0;
                    self.render();
                    if (response.successful) {
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                },
                error: function(model, response) {
                    messenger.showErrorMessage(__('oro.email.error.mark_as_read'), response.responseJSON || {});
                }
            });
        },

        onSeenStatusChange: function(model, isSeen) {
            if (isSeen && this.countNewEmail > 0) {
                this.countNewEmail--;
            } else {
                this.countNewEmail++;
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

        onResetCollection: function() {
            this.collection.unreadEmailsCount = 0;
        },

        onClickIconEnvelope: function() {
            if (this.isActiveTypeDropDown('notification')) {
                this.open();
                this.setModeDropDownMenu('content');
            }
            this.updateViewMode();
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
        },

        onCollectionRequest: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
                this.loadingMask.dispose();
            }
            this.loadingMask = new LoadingMask({
                container: this.$('.content')
            });
            this.loadingMask.show();
        },

        onCollectionSync: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
                this.loadingMask.dispose();
            }
            this.render();
        },

        dispose: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
                this.loadingMask.dispose();
            }
            EmailNotificationCollectionView.__super__.dispose.call(this);
        }
    });

    return EmailNotificationCollectionView;
});
