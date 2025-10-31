import Backbone from 'backbone';
import _ from 'underscore';
import constants from 'orosidebar/js/sidebar-constants';
import BaseView from 'oroui/js/app/views/base/view';
import EmailNotificationComponent from 'oroemail/js/app/components/email-notification-component';

const RecentEmailsContentView = BaseView.extend({
    component: null,

    listen: {
        refresh: 'onRefresh'
    },

    listenToUpdatePosition: true,

    /**
     * @inheritdoc
     */
    constructor: function RecentEmailsContentView(options) {
        RecentEmailsContentView.__super__.constructor.call(this, options);
    },

    render: function() {
        if (this.model.notificationComponentInstance) {
            this.model.notificationComponentInstance.dispose();
        }
        const settings = this.model.get('settings');
        const options = {
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
        const emailNotificationView = this.model.notificationComponentInstance.view;

        if (this.model.collection.findWhere({state: constants.WIDGET_MAXIMIZED_HOVER}) !== void 0 &&
            emailNotificationView instanceof Backbone.View &&
            !emailNotificationView.disposed &&
            _.isFunction(emailNotificationView.adjustMaxHeight)
        ) {
            emailNotificationView.adjustMaxHeight();
        }
    }
});

export default RecentEmailsContentView;
