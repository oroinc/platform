define(function(require) {
    'use strict';

    var EmailAttachmentContextView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    EmailAttachmentContextView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['enableAttachmentSelector']),
        /**
         * @inheritDoc
         */
        constructor: function EmailAttachmentContextView(options) {
            this.$enableAttachment = $(options.el).closest('form').find(options.enableAttachmentSelector);

            EmailAttachmentContextView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            EmailAttachmentContextView.__super__.initialize.apply(this, arguments);

            this.setVisibility();
        },

        delegateEvents: function(events) {
            EmailAttachmentContextView.__super__.undelegateEvents.call(this, events);
            this.$enableAttachment.on('change' + this.eventNamespace(), this.onAttachmentEnableToggle.bind(this));
        },

        undelegateEvents: function() {
            if (this.$enableAttachment) {
                this.$enableAttachment.off(this.eventNamespace());
            }
            EmailAttachmentContextView.__super__.undelegateEvents.call(this);
        },

        onAttachmentEnableToggle: function(e) {
            this.setVisibility();
        },

        setVisibility: function() {
            if (parseInt(this.$enableAttachment.val())) {
                this.$el.attr('disabled', false);
                this.$el.parent('div').removeClass('disabled');
            } else {
                this.$el.attr('disabled', true);
                this.$el.parent('div').addClass('disabled');
            }
        },

        dispose: function() {
            delete this.$enableAttachment;
            EmailAttachmentContextView.__super__.dispose.apply(this, arguments);
        }
    });

    return EmailAttachmentContextView;
});
