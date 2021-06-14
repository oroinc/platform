define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const UnreadEmailsStateModel = require('oroemail/js/app/models/unread-emails-state-model');
    const sync = require('orosync/js/sync');
    const EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    const config = require('module-config').default(module.id);
    const channel = config.wsChannel;
    let instance;

    function UnreadEmailsStateHolder() {
        this.unreadEmailsStateModel = new UnreadEmailsStateModel();
        this.emailNotificationCollection = new EmailNotificationCollection();
        sync.subscribe(channel, _.debounce(this._notificationHandler.bind(this), 1000));
    }
    _.extend(UnreadEmailsStateHolder.prototype, {
        getModel: function() {
            return this.unreadEmailsStateModel;
        },
        _notificationHandler: function() {
            this.emailNotificationCollection.fetch({
                success: this._onFetchSuccess.bind(this)
            });
        },
        _onFetchSuccess: function(collection) {
            const unreadEmails = _.pluck(_.where(collection.toJSON(), {seen: false}), 'id');
            this.unreadEmailsStateModel.set('count', collection.unreadEmailsCount);
            this.unreadEmailsStateModel.set('ids', unreadEmails);
        }
    });

    return {
        getModel: function() {
            if (instance === void 0) {
                instance = new UnreadEmailsStateHolder();
            }
            return instance.getModel();
        }
    };
});
