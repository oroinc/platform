define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var BaseView = require('oroui/js/app/views/base/view');
    var EmailNotificationComponent = require('oroemail/js/app/components/email-notification-component');

    RecentEmailsContentView = BaseView.extend({
        component: null,

        listen: {
            'refresh': 'onRefresh'
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
        }
    });

    return RecentEmailsContentView;
});
