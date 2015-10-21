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
        /**
         * @type {Function}
         */
        notificationHandler: null,
        clankEvent: '',
        dropdownContainer: null,
        listen: {
            'sync collection': 'updateCountModel',
            'widget_dialog:open mediator': 'onWidgetDialogOpen'
        },

        initialize: function(options) {
            var emails = options.emails || [];
            _.extend(this, _.pick(options, ['clankEvent']));
            if (typeof emails === 'string') {
                emails = JSON.parse(emails);
            }
            this.collection = new EmailNotificationCollection(emails);
            this.countModel = new EmailNotificationCountModel({'unreadEmailsCount': options.count});
            this.dropdownContainer = options._sourceElement.parent();

            this.notificationHandler = _.debounce(_.bind(this._notificationHandler, this), 1000);
            sync.subscribe(this.clankEvent, this.notificationHandler);

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
            sync.unsubscribe(this.clankEvent, this.notificationHandler);
            UserMenuEmailNotificationComponent.__super__.dispose.call(this);
        }
    });

    return UserMenuEmailNotificationComponent;
});
