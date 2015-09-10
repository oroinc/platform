define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.model = options.model;
            this.model.collection = new EmailNotificationCollection([]);
            this.model.collection.fetch();
            this.model.collection.on('sync', this.onCollectionSync, this);
        },

        onCollectionSync: function() {
            this.model.set({
                unreadEmailsCount: this.model.collection.unreadEmailsCount ?
                    this.model.collection.unreadEmailsCount : ''
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.model.collection.off('sync', this.onCollectionSync, this);
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
