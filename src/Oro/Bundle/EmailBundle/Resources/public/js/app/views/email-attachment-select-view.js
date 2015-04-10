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
        isShowed: false,
        fileNameFilter: '',

        events: {
            'click .cancel':                 'cancelClick',
            'click .upload-new':             'uploadNewClick',
            'click .attach':                 'attachClick',
            'change input.input-upload-new': 'fileSelected',
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

        fileSelected: function(event) {
            if ($(event.target).val()) {
                var files = event.target.files;
                var data = new FormData();
                $.each(files, function (key, value) {
                    data.append(key, value);
                });

                var self = this;

                $.ajax({
                    url: routing.generate('oro_email_attachment_upload'),
                    type: 'POST',
                    data: data,
                    cache: false,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function (data, textStatus, jqXHR) {
                        self.collection.add(data.attachments)
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('error');
                    }
                });
            }
        },

        filterChange: function(event) {
            var value = $(event.target).val();

            this.collection.each(function(model) {
                if (model.get('fileName').indexOf(value) === 0) {
                    model.set('visible', true)
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
