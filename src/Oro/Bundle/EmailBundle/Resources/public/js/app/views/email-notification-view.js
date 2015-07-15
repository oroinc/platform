/*global define*/
define([
    'jquery',
    'oroemail/js/app/models/email-attachment-model',
    'oroui/js/app/views/base/view'
], function ($, EmailAttachmentModel, BaseView) {
    'use strict';

    var EmailAttachmentView;

    EmailAttachmentView = BaseView.extend({
        contextsView: null,
        //model: EmailAttachmentModel,
        inputName: '',

        events: {
            'click a.mark-as-read': 'onClickMarkAsRead'
        },

        onClickMarkAsRead: function () {
            alert(2);
        }
    });

    return EmailAttachmentView;
});
