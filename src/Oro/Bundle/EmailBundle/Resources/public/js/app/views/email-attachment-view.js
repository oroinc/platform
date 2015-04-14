/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentView,
        $ = require('jquery'),
        EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        model: EmailAttachmentModel,
        inputName: '',

        events: {
            'click i.icon-remove': 'removeClick'
        },

        listen: {
            'change:fileName model': 'fileNameChange',
            'change:type model':     'typeChange'
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-item').html();
            }

            return EmailAttachmentView.__super__.getTemplateFunction.call(this);
        },

        getTemplateData: function() {
            var data = EmailAttachmentView.__super__.getTemplateData.call(this);
            data.cid = this.model.cid;
            data.inputName = this.inputName;

            return data;
        },

        removeClick: function() {
            this.model.trigger('destroy', this.model);
        },

        fileSelect: function() {
            var self = this;
            var $fileInput = this.$('input[type="file"]');
            this.$el.hide();

            $fileInput.on('change', function() {
                var value = $fileInput.val().replace(/^.*[\\\/]/, '');

                if (value) {
                    self.model.set('fileName', value);
                    self.model.set('type', 3);
                    self.$el.show();

                    self.collectionView.show();
                }
            });
            $fileInput.click();
        },

        fileNameChange: function() {
            this.$('span.filename-label').html(this.model.get('fileName'));
        },

        typeChange: function() {
            this.$('input.attachment-type').val(this.model.get('type'));
        }
    });

    return EmailAttachmentView;
});
