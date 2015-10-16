define(function(require) {
    'use strict';

    var EmailNotificationComponent;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DesktopEmailNotificationView =
        require('oroemail/js/app/views/email-notification/email-notification-collection-view');
    var MobileEmailNotificationView =
        require('oroemail/js/app/views/email-notification/mobile-email-notification-view');
    var EmailNotificationCountView =
        require('oroemail/js/app/views/email-notification/email-notification-count-view');

    EmailNotificationComponent = BaseComponent.extend({
        view: null,
        collection: null,
        countModel: null,

        initialize: function(options) {
            _.extend(this, _.pick(options, ['countModel']));
            if (this.countModel instanceof Backbone.Model === false) {
                throw new TypeError('Invalid "countModel" option of EmailNotificationComponent');
            }
            this.initViews(options);
        },

        initViews: function(options) {
            var EmailNotificationView = tools.isMobile() ? MobileEmailNotificationView : DesktopEmailNotificationView;
            this.view = new EmailNotificationView({
                el: options._sourceElement,
                collection: this.collection,
                countNewEmail: this.countModel.get('unreadEmailsCount'),
                folderId: options.folderId,
                defaultActionId: options.defaultActionId,
                hasMarkAllButton: Boolean(options.hasMarkAllButton),
                hasMarkVisibleButton: Boolean(options.hasMarkVisibleButton)
            });
            if (options._iconElement && options._iconElement.length) {
                this.countView = new EmailNotificationCountView({
                    el: options._iconElement,
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

    return EmailNotificationComponent;
});
