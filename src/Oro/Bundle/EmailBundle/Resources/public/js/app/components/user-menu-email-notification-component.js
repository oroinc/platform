import _ from 'underscore';
import sync from 'orosync/js/sync';
import mediator from 'oroui/js/mediator';
import EmailNotificationCollection from 'oroemail/js/app/models/email-notification/email-notification-collection';
import EmailNotificationCountModel from 'oroemail/js/app/models/email-notification/email-notification-count-model';
import EmailNotificationComponent from 'oroemail/js/app/components/email-notification-component';

const UserMenuEmailNotificationComponent = EmailNotificationComponent.extend({
    collection: null,

    countModel: null,

    /**
     * @type {Function}
     */
    notificationHandler: null,

    wsChannel: '',

    dropdownContainer: null,

    listen: {
        'sync collection': 'updateCountModel',
        'widget_dialog:open mediator': 'onWidgetDialogOpen'
    },

    /**
     * @inheritdoc
     */
    constructor: function UserMenuEmailNotificationComponent(options) {
        UserMenuEmailNotificationComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        let emails = options.emails || [];
        _.extend(this, _.pick(options, ['wsChannel']));
        if (typeof emails === 'string') {
            emails = JSON.parse(emails);
        }
        this.collection = new EmailNotificationCollection(emails);
        this.countModel = new EmailNotificationCountModel({unreadEmailsCount: options.count});
        this.dropdownContainer = options._sourceElement;

        this.notificationHandler = _.debounce(this._notificationHandler.bind(this), 1000);
        sync.subscribe(this.wsChannel, this.notificationHandler);

        UserMenuEmailNotificationComponent.__super__.initialize.call(this, options);
    },

    _notificationHandler: function() {
        this.collection.fetch();
        mediator.trigger('datagrid:doRefresh:user-email-grid');
    },

    updateCountModel: function(collection) {
        this.countModel.set('unreadEmailsCount', collection.unreadEmailsCount);
    },

    onWidgetDialogOpen: function() {
        this.dropdownContainer.trigger('tohide.bs.dropdown');
    },

    dispose: function() {
        sync.unsubscribe(this.wsChannel, this.notificationHandler);
        UserMenuEmailNotificationComponent.__super__.dispose.call(this);
    }
});

export default UserMenuEmailNotificationComponent;
