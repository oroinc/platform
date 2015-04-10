/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentSelectView,
        EmailAttachmentListRowView = require('oroemail/js/app/views/email-attachment-list-row-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    EmailAttachmentSelectView = BaseCollectionView.extend({
        itemView: EmailAttachmentListRowView,
        isShowed: false,

        events: {
            'click .cancel':     'cancelClick',
            'click .upload-new': 'uploadNewClick',
            'click .attach':     'attachClick'
        },

        cancelClick: function() {
            this.hide();
        },

        attachClick: function() {
            this.collection.trigger('attach');
        },

        uploadNewClick: function() {
            console.log('For implementation');
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-select-view').html();
            }

            return EmailAttachmentSelectView.__super__.getTemplateFunction.call(this);
        },

        show: function() {
            this.$el.show();
            this.isShowed = true;
        },

        hide: function() {
            this.$el.hide();
            this.isShowed = false;
        }
    });

    return EmailAttachmentSelectView;
});
