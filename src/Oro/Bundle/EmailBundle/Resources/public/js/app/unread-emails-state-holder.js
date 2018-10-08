define(function(require) {
    'use strict';
    var UnreadEmailsStateHolder;
    var _ = require('underscore');
    var UnreadEmailsStateModel = require('oroemail/js/app/models/unread-emails-state-model');
    var sync = require('orosync/js/sync');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var module = require('module');
    var channel = module.config().wsChannel;
    var instance;

    UnreadEmailsStateHolder = function() {
        this.unreadEmailsStateModel = new UnreadEmailsStateModel();
        this.emailNotificationCollection = new EmailNotificationCollection();
        sync.subscribe(channel, _.debounce(_.bind(this._notificationHandler, this), 1000));
    };
    _.extend(UnreadEmailsStateHolder.prototype, {
        getModel: function() {
            return this.unreadEmailsStateModel;
        },
        _notificationHandler: function() {
            this.emailNotificationCollection.fetch({
                success: _.bind(this._onFetchSuccess, this)
            });
        },
        _onFetchSuccess: function(collection) {
            var unreadEmails = _.pluck(_.where(collection.toJSON(), {seen: false}), 'id');
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
