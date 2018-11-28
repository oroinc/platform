define(function(require) {
    'use strict';

    var EmailAttachmentView;
    var EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        model: EmailAttachmentModel,

        inputName: '',

        events: {
            'click i.fa-close': 'removeClick'
        },

        listen: {
            'change:fileName model': 'fileNameChange',
            'change:type model': 'typeChange',
            'change:icon model': 'iconChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailAttachmentView() {
            EmailAttachmentView.__super__.constructor.apply(this, arguments);
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = require('tpl!oroemail/templates/email-attachment/email-attachment-item.html');
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
            this.$('.attachment-item__filename')
                .html(this.model.get('fileName'))
                .attr('title', this.model.get('fileName'));
        },

        typeChange: function() {
            this.$('input.attachment-type').val(this.model.get('type'));
        },

        iconChange: function() {
            this.$('.attachment-item .fa').addClass(this.model.get('icon'));
        }
    });

    return EmailAttachmentView;
});
