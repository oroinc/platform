/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentListRowView,
        $ = require('jquery'),
        EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailAttachmentListRowView = BaseView.extend({
        model: EmailAttachmentModel,

        events: {
            'click input[type="checkbox"]': 'checkboxClick'
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-list-row-view').html();
            }

            return EmailAttachmentListRowView.__super__.getTemplateFunction.call(this);
        },

        checkboxClick: function() {
            this.model.toggleChecked();
        }
    });

    return EmailAttachmentListRowView;
});
