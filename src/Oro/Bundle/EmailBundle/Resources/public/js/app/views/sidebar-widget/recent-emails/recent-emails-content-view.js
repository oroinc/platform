define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var BaseView = require('oroui/js/app/views/base/view');
    var EmailNotificationComponent = require('oroemail/js/app/components/email-notification-component');

    RecentEmailsContentView = BaseView.extend({
        component: null,

        render: function() {
            if (this.component) {
                this.component.dispose();
            }

            var options = {
                _sourceElement: this.$el,
                collection: this.model.emailNotificationCollection,
                countModel: this.model
            };

            this.component = new EmailNotificationComponent(options);

            return this;
        }

    });

    return RecentEmailsContentView;
});
