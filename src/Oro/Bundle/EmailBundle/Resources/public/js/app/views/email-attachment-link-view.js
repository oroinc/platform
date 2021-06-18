define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'backbone',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator'
], function($, _, __, Backbone, messenger, BaseView, mediator) {
    'use strict';

    /**
     * @export oroemail/js/app/views/email-attachment-link-view
     */
    const EmailAttachmentLink = BaseView.extend({
        options: {},
        events: {
            click: 'linkAttachment'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentLink(options) {
            EmailAttachmentLink.__super__.constructor.call(this, options);
        },

        /**
        * Constructor
        *
        * @param options {Object}
        */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            EmailAttachmentLink.__super__.initialize.call(this, options);
        },

        /**
         * onClick event listener
         */
        linkAttachment: function(e) {
            const self = this;
            e.preventDefault();
            $.post(
                this.options.url,
                {},
                function(response) {
                    if (_.isUndefined(response.error)) {
                        messenger.notificationFlashMessage('success', __('oro.email.attachment.added'));
                        self.$el.parent().addClass('one');
                        self.$el.remove();
                        self.reloadAttachmentGrid();
                    } else {
                        messenger.notificationFlashMessage('error', __(response.error));
                    }
                },
                'json'
            );
            return false;
        },

        reloadAttachmentGrid: function() {
            mediator.trigger('datagrid:doRefresh:attachment-grid');
        }
    });

    return EmailAttachmentLink;
});
