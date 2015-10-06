define(function(require) {
    'use strict';

    var EmailNotificationCountView;
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationCountView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroemail/templates/email-notification/email-notification-icon-view.html'),
        initialize: function() {
            console.log('init-count-view')
            EmailNotificationCountView.__super__.initialize.apply(this, arguments);
            this.listenTo(this.model, 'change', this.render);
        },

        render: function() {
            console.log('render');
            return EmailNotificationCountView.__super__.render.apply(this, arguments);
        }
    });

    return EmailNotificationCountView;
});
