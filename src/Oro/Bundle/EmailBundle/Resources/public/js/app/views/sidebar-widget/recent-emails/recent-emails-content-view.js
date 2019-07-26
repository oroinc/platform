define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var Backbone = require('backbone');
    var _ = require('underscore');
    var constants = require('orosidebar/js/sidebar-constants');
    var BaseView = require('oroui/js/app/views/base/view');
    var EmailNotificationComponent = require('oroemail/js/app/components/email-notification-component');

    RecentEmailsContentView = BaseView.extend({
        component: null,

        listen: {
            refresh: 'onRefresh'
        },

        listenToUpdatePosition: true,

        /**
         * @inheritDoc
         */
        constructor: function RecentEmailsContentView() {
            RecentEmailsContentView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            if (this.model.notificationComponentInstance) {
                this.model.notificationComponentInstance.dispose();
            }
            var settings = this.model.get('settings');
            var options = {
                _sourceElement: this.$el,
                collection: this.model.emailNotificationCollection,
                countModel: this.model.emailNotificationCountModel,
                defaultActionId: settings.defaultActionId,
                folderId: settings.folderId,
                hasMarkAllButton: false,
                hasMarkVisibleButton: true
            };

            this.model.notificationComponentInstance = new EmailNotificationComponent(options);

            return this;
        },

        onRefresh: function() {
            this.model.emailNotificationCollection.fetch();
        },

        onUpdatePosition: function() {
            var emailNotificationView = this.model.notificationComponentInstance.view;

            if (this.model.collection.findWhere({state: constants.WIDGET_MAXIMIZED_HOVER}) !== void 0 &&
                emailNotificationView instanceof Backbone.View &&
                !emailNotificationView.disposed &&
                _.isFunction(emailNotificationView.adjustMaxHeight)
            ) {
                emailNotificationView.adjustMaxHeight();
            }
        }
    });

    return RecentEmailsContentView;
});
