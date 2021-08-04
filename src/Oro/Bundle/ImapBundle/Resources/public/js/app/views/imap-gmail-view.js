define(function(require) {
    'use strict';

    const BaseView = require('oroimap/js/app/views/imap-view');
    const ImapGmailView = BaseView.extend({
        /**
         * @inheritdoc
         */
        constructor: function ImapGmailView(options) {
            ImapGmailView.__super__.constructor.call(this, options);
        }
    });

    return ImapGmailView;
});
