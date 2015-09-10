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

            var that = this;
            var options = {
                _sourceElement: this.$el
            };
            this.component = new EmailNotificationComponent(options);

            this.component.loadLastEmail(false);

            this.component.collection.on('reset', function(collection) {
                that.model.set('count', collection.length);
            });

            return this;
        }

    });

    return RecentEmailsContentView;
});
