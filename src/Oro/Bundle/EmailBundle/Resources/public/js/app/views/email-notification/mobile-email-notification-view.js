define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const MobileEmailNotificationView = BaseView.extend({
        autoRender: true,

        /**
         * @type {number}
         */
        countNewEmail: null,

        /**
         * @inheritDoc
         */
        constructor: function MobileEmailNotificationView(options) {
            MobileEmailNotificationView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            MobileEmailNotificationView.__super__.initialize.call(this, options);
            this.countNewEmail = parseInt(options.countNewEmail);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            const $emailsMenuItem = $('#user-menu .oro-email-user-emails a');
            this.$counter = $('<span class="email-count"/>').appendTo($emailsMenuItem);
            this.setCount(this.countNewEmail);
        },

        remove: function() {
            this.$counter.remove();
            MobileEmailNotificationView.__super__.remove.call(this);
        },

        setCount: function(count) {
            this.countNewEmail = count = parseInt(count);
            if (count === 0) {
                count = '';
            } else {
                count = '(' + (count > 99 ? '99+' : count) + ')';
            }
            this.$counter.html(count);
            $('#user-menu .dropdown-toggle').toggleClass('has-new-emails', Boolean(this.countNewEmail));
        }
    });

    return MobileEmailNotificationView;
});
