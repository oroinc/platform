define(function(require) {
    'use strict';

    var EmailAttachmentView;
    var $ = require('jquery');
    var EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        model: EmailAttachmentModel,
        inputName: '',

        events: {
            'click i.icon-remove': 'removeClick'
        },

        listen: {
            'change:fileName model': 'fileNameChange',
            'change:type model':     'typeChange',
            'change:icon model':     'iconChange'
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
                    var extension = value.substr(value.lastIndexOf('.') + 1);
                    var icon = self.fileIcons['default'];
                    if (extension && self.fileIcons[extension]) {
                        icon = self.fileIcons[extension];
                    }
                    self.model.set('icon', icon);
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
        },

        iconChange: function() {
            this.$('.filename .fa').addClass(this.model.get('icon'));
        }
    });

    return EmailAttachmentView;
});
