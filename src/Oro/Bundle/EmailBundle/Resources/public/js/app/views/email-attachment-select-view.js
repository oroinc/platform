/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentSelectView,
        $ = require('jquery'),
        routing = require('routing'),
        EmailAttachmentListRowView = require('oroemail/js/app/views/email-attachment-list-row-view'),
        BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    EmailAttachmentSelectView = BaseCollectionView.extend({
        itemView: EmailAttachmentListRowView,
        listSelector: '.attachment-list',
        fallbackSelector: '.no-items',
        isShowed: false,
        fileNameFilter: '',

        events: {
            'click .cancel':                 'cancelClick',
            'click .upload-new':             'uploadNewClick',
            'click .attach':                 'attachClick',
            'input input.filter':            'filterChange'
        },

        cancelClick: function() {
            this.hide();
        },

        attachClick: function() {
            this.collection.trigger('attach');
        },

        uploadNewClick: function() {
            this.$('input.input-upload-new').click();
        },

        filterChange: function(event) {
            var value = $(event.target).val();

            this.collection.each(function(model) {
                if (model.get('fileName').indexOf(value) === 0) {
                    model.set('visible', true);
                } else {
                    model.set('visible', false);
                }
            });
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
