define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const emailsGridRouteBuilder = require('oroemail/js/util/emails-grid-route-builder');
    const EmailNotificationView = require('./email-notification-item-view');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');

    const EmailNotificationCollectionView = BaseCollectionView.extend({
        template: require('tpl-loader!oroemail/templates/email-notification/email-notification-collection-view.html'),
        itemView: EmailNotificationView,
        animationDuration: 0,
        listSelector: '.items',
        countNewEmail: 0,
        folderId: 0,
        hasMarkAllButton: true,
        hasMarkVisibleButton: false,
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
            'sync collection': 'onCollectionSync',
            'layout:reposition mediator': 'adjustMaxHeight'
        },

        events: {
            'click': 'onClickIconEnvelope',
            'click button.mark-as-read': 'onClickMarkAsRead',
            'click button.mark-visible-as-read': 'onClickMarkVisibleAsRead'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationCollectionView(options) {
            EmailNotificationCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            EmailNotificationCollectionView.__super__.initialize.call(this, options);
            _.extend(this, _.pick(options, ['folderId', 'hasMarkAllButton', 'hasMarkVisibleButton']));
            this.countNewEmail = parseInt(options.countNewEmail);
            if (options.defaultActionId) {
                this.defaultActionId = parseInt(options.defaultActionId);
            }
        },

        render: function() {
            EmailNotificationCollectionView.__super__.render.call(this);
            this.updateViewMode();
            _.defer(this.adjustMaxHeight.bind(this));
        },

        getTemplateData: function() {
            const data = EmailNotificationCollectionView.__super__.getTemplateData.call(this);
            const visibleUnreadEmails = this.collection.filter(function(item) {
                return item.get('seen') === false;
            }).length;
            _.extend(data, _.pick(this, [
                'defaultActionId',
                'countNewEmail',
                'folderId',
                'hasMarkAllButton',
                'hasMarkVisibleButton']));
            data.userEmailsUrl = emailsGridRouteBuilder.generate(this.folderId);
            data.moreUnreadEmails = Math.max(this.countNewEmail - visibleUnreadEmails, 0);
            return data;
        },

        updateViewMode: function() {
            if (!this.isActiveTypeDropDown('notification')) {
                const $iconEnvelope = this.$el.find('.dropdown-toggle .fa-envelope');
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

        adjustMaxHeight: function() {
            let rect;
            let contentRect;
            let maxHeight;
            let $list;
            if (this.el) {
                maxHeight = parseInt(this.$el.css('max-height'));
                $list = this.$list;
                if ($list.length === 1 && !isNaN(maxHeight)) {
                    rect = this.$el[0].getBoundingClientRect();
                    contentRect = $list.parent()[0].getBoundingClientRect();
                    $list.css('max-height', rect.top + maxHeight + $list.height() - contentRect.bottom + 'px');
                }
            }
        },

        _markAsRead: function(ids) {
            $.ajax({
                method: 'POST',
                url: routing.generate('oro_email_mark_all_as_seen', ids),
                success: response => {
                    this.collection.markAllAsRead();
                    this.collection.unreadEmailsCount = 0;
                    this.countNewEmail = 0;
                    this.render();
                    if (response.successful) {
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                },
                errorHandlerMessage: __('oro.email.error.mark_as_read')
            });
        },

        onClickMarkVisibleAsRead: function() {
            const ids = [];
            this.collection.each(function(email) {
                if (email.get('seen') === false) {
                    ids.push(email.get('id'));
                }
            });
            this._markAsRead({ids: ids});
        },

        onClickMarkAsRead: function() {
            this._markAsRead({});
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

        isOpen: function() {
            this.$el.hasClass('show');
        },

        close: function() {
            this.$el.removeClass('show');
        },

        open: function() {
            this.$el.addClass('show');
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
                this.loadingMask = null;
            }
            this.render();
        },

        dispose: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
                this.loadingMask.dispose();
                this.loadingMask = null;
            }
            EmailNotificationCollectionView.__super__.dispose.call(this);
        }
    });

    return EmailNotificationCollectionView;
});
