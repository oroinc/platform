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

    var EmailAttachmentLink;

    /**
     * @export oroemail/js/app/views/email-attachment-link-view
     */
    EmailAttachmentLink = BaseView.extend({
        options: {},
        events: {
            'click': 'linkAttachment'
        },

        /**
        * Constructor
        *
        * @param options {Object}
        */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            EmailAttachmentLink.__super__.initialize.apply(this, arguments);
        },

        /**
         * onClick event listener
         */
        linkAttachment: function(e) {
            var self = this;
            e.preventDefault();
            $.getJSON(
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
                }
            );
            return false;
        },

        reloadAttachmentGrid: function() {
            mediator.trigger('datagrid:doRefresh:attachment-grid');
        }
    });

    return EmailAttachmentLink;
});
