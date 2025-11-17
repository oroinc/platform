import _ from 'underscore';
import Backbone from 'backbone';
import tools from 'oroui/js/tools';
import BaseComponent from 'oroui/js/app/components/base/component';
import DesktopEmailNotificationView from 'oroemail/js/app/views/email-notification/email-notification-collection-view';
import MobileEmailNotificationView from 'oroemail/js/app/views/email-notification/mobile-email-notification-view';
import EmailNotificationCountView from 'oroemail/js/app/views/email-notification/email-notification-count-view';

const EmailNotificationComponent = BaseComponent.extend({
    view: null,

    collection: null,

    countModel: null,

    /**
     * @inheritdoc
     */
    constructor: function EmailNotificationComponent(options) {
        EmailNotificationComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        _.extend(this, _.pick(options, ['countModel']));
        if (this.countModel instanceof Backbone.Model === false) {
            throw new TypeError('Invalid "countModel" option of EmailNotificationComponent');
        }
        this.initViews(options);
    },

    initViews: function(options) {
        const EmailNotificationView = tools.isMobile() ? MobileEmailNotificationView : DesktopEmailNotificationView;
        this.view = new EmailNotificationView({
            el: options.listSelector ? options._sourceElement.find(options.listSelector) : options._sourceElement,
            collection: this.collection,
            countNewEmail: this.countModel.get('unreadEmailsCount'),
            folderId: options.folderId,
            defaultActionId: options.defaultActionId,
            hasMarkAllButton: Boolean(options.hasMarkAllButton),
            hasMarkVisibleButton: Boolean(options.hasMarkVisibleButton)
        });
        let iconElement;
        if (options.iconSelector && (iconElement = options._sourceElement.find(options.iconSelector)).length) {
            this.countView = new EmailNotificationCountView({
                el: iconElement,
                model: this.countModel
            });
        }
    },

    dispose: function() {
        delete this.collection;
        delete this.countModel;
        EmailNotificationComponent.__super__.dispose.call(this);
    }
});

export default EmailNotificationComponent;
