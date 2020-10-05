define(function(require) {
    'use strict';

    const BaseView = require('oroimap/js/app/views/imap-view');
    const ImapMicrosoftView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ImapMicrosoftView(options) {
            ImapMicrosoftView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        getData: function() {
            const data = ImapMicrosoftView.__super__.getData.call(this);
            data.tenant = this.$el.find('input[name$="[userEmailOrigin][tenant]"]').val();

            return data;
        }
    });

    return ImapMicrosoftView;
});
