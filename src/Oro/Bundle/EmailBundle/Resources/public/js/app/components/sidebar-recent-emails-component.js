define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        listen: {
            'change:settings model': 'update'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.model = options.model;
            this.model.emailNotificationCollection = new EmailNotificationCollection([]);
            this.update(this.model, this.model.get('settings'));
            this.model.emailNotificationCollection.on('sync', this.onCollectionSync, this);
        },

        onCollectionSync: function() {
            this.model.set({
                unreadEmailsCount: this.model.emailNotificationCollection.unreadEmailsCount || ''
            });
        },

        update: function(model, settings) {
            var title;
            if (settings.folderName) {
                title = settings.folderName;
                if (settings.mailboxName) {
                    title += ' - ' + settings.mailboxName;
                }
                this.model.set({'title': title}, {silent: true});
            }
            model.emailNotificationCollection.setRouteParams(settings);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.model.emailNotificationCollection.off('sync', this.onCollectionSync, this);
            delete this.model;
            this.model.emailNotificationCollection.dispose();
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
