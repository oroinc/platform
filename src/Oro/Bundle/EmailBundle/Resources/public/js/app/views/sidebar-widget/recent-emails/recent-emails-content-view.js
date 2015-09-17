define(function(require) {
    'use strict';

    var RecentEmailsContentView;
    var BaseView = require('oroui/js/app/views/base/view');
    var EmailNotificationComponent = require('oroemail/js/app/components/email-notification-component');

    RecentEmailsContentView = BaseView.extend({
        component: null,

        initialize: function() {
            this.on('refresh', this.onRefresh);
            RecentEmailsContentView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            if (this.component) {
                this.component.dispose();
            }

            var settings = this.model.get('settings');
            var collection = this.model.emailNotificationCollection;
            var options = {
                _sourceElement: this.$el,
                collection: collection,
                countModel: this.model
            };

            this.component = new EmailNotificationComponent(options);

            return this;
        },
        onRefresh: function() {
            this.model.emailNotificationCollection.fetch();
        }

    });

    return RecentEmailsContentView;
});
