define(function(require) {
    'use strict';

    var UserMenuEmailNotificationComponent;
    var _ = require('underscore');
    var sync = require('orosync/js/sync');
    var mediator = require('oroui/js/mediator');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var EmailNotificationCountModel =
        require('oroemail/js/app/models/email-notification/email-notification-count-model');
    var EmailNotificationComponent = require('oroemail/js/app/components/email-notification-component');

    UserMenuEmailNotificationComponent = EmailNotificationComponent.extend({
        collection: null,
        countModel: null,
        debouncedNotificationHandler: null,
        clankEvent: '',
        dropdownContainer: null,
        listen: {
            'sync collection': 'updateCountModel',
            'widget_dialog:open mediator': 'onWidgetDialogOpen'
        },

        initialize: function(options) {
            var emails = options.emails || [];
            _.extend(this, _.pick(options, ['clankEvent']));
            this.debouncedNotificationHandler = _.debounce(_.bind(this._notificationHandler, this), 3000, true);
            if (typeof emails === 'string') {
                emails = JSON.parse(emails);
            }
            this.collection = new EmailNotificationCollection(emails);
            this.countModel = new EmailNotificationCountModel({'unreadEmailsCount': options.count});
            this.dropdownContainer = options._sourceElement.parent();

            sync.subscribe(this.clankEvent, this.debouncedNotificationHandler);

            UserMenuEmailNotificationComponent.__super__.initialize.apply(this, arguments);
        },

        _notificationHandler: function() {
            this.collection.fetch();
            mediator.trigger('datagrid:doRefresh:user-email-grid');
        },

        updateCountModel: function(collection) {
            this.countModel.set('unreadEmailsCount', collection.unreadEmailsCount);
        },

        onWidgetDialogOpen: function() {
            this.dropdownContainer.removeClass('open');
        },

        dispose: function() {
            sync.unsubscribe(this.clankEvent, this.debouncedNotificationHandler);
            UserMenuEmailNotificationComponent.__super__.dispose.call(this);
        }
    });

    return UserMenuEmailNotificationComponent;
});
