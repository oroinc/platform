define(function(require) {
    'use strict';

    var EmailAttachmentContextView;
    var BaseView = require('oroui/js/app/views/base/view');

    EmailAttachmentContextView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['enableAttachmentSelector']),

        initialize: function(options) {
            EmailAttachmentContextView.__super__.initialize.apply(this, arguments);

            this.attachmentSelectEl = this.$el.closest('form').find(this.enableAttachmentSelector);
            this.attachmentSelectEl.on('change' + this.eventNamespace(), this.onAttachmentEnableToggle.bind(this));

            this.setVisibility();
        },

        onAttachmentEnableToggle: function(e) {
            this.setVisibility();
        },

        setVisibility: function() {
            if (parseInt(this.attachmentSelectEl.val())) {
                this.$el.attr('disabled', false);
                this.$el.parent('div').removeClass('disabled');
            } else {
                this.$el.attr('disabled', true);
                this.$el.parent('div').addClass('disabled');
            }
        },

        dispose: function() {
            EmailAttachmentContextView.__super__.dispose.apply(this, arguments);

            this.attachmentSelectEl.off(this.eventNamespace());
        }

    });

    return EmailAttachmentContextView;
});
