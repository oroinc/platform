/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'backbone',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view'
], function ($, _, __, Backbone, messenger, BaseView) {
    'use strict';

    var EmailAttachmentLink;

    /**
     * @export oroemail/js/app/views/email-attachment-link-view
     */
    EmailAttachmentLink = BaseView.extend({
        options: {},
        events: {
            'click .icon-link': 'linkAttachment'
        },

        /**
        * Constructor
        *
        * @param options {Object}
        */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            EmailAttachmentLink.__super__.initialize.apply(this, arguments);
        },

        /**
         * onClick event listener
         */
        linkAttachment: function (e) {
            e.preventDefault();
            $.getJSON(
                this.options.url,
                {},
                function (response) {
                    if (_.isUndefined(response.error)) {
                        messenger.notificationFlashMessage('success', __('oro.email.attachment.added'));
                    } else {
                        messenger.notificationFlashMessage('error', __(response.error));
                    }
                }
            );
            return false;
        }
    });

    return EmailAttachmentLink;
});
