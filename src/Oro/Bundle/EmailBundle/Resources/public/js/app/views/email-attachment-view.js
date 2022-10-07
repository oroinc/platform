define(function(require) {
    'use strict';

    const EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model');
    const BaseView = require('oroui/js/app/views/base/view');

    const EmailAttachmentView = BaseView.extend({
        model: EmailAttachmentModel,

        inputName: '',

        events: {
            'click [data-role="remove"]': 'removeClick'
        },

        listen: {
            'change:fileName model': 'fileNameChange',
            'change:type model': 'typeChange',
            'change:icon model': 'iconChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentView(options) {
            EmailAttachmentView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            this.guessFileIcon();
        },

        getTemplateFunction() {
            if (!this.template) {
                this.template = require('tpl-loader!oroemail/templates/email-attachment/email-attachment-item.html');
            }

            return EmailAttachmentView.__super__.getTemplateFunction.call(this);
        },

        getTemplateData() {
            const data = EmailAttachmentView.__super__.getTemplateData.call(this);
            data.cid = this.model.cid;
            data.inputName = this.inputName;

            return data;
        },

        removeClick() {
            this.model.trigger('destroy', this.model);
        },

        fileSelect() {
            const $fileInput = this.$('input[type="file"]');
            this.$el.hide();

            $fileInput.on('change', () => {
                const value = $fileInput.val().replace(/^.*[\\\/]/, '');

                if (value) {
                    this.model.set('fileName', value);
                    this.model.set('type', 3);
                    this.guessFileIcon();
                    this.$el.show();

                    this.collectionView.show();

                    this.$el.trigger('content:changed');
                }
            });
            $fileInput.click();
        },

        guessFileIcon() {
            const value = this.model.get('fileName');
            if (value) {
                const extension = value.substr(value.lastIndexOf('.') + 1);
                let icon = this.fileIcons['default'];
                if (extension && this.fileIcons[extension]) {
                    icon = this.fileIcons[extension];
                }
                this.model.set('icon', icon);
            }
        },

        fileNameChange() {
            this.$('.attachment-item__filename')
                .html(this.model.get('fileName'))
                .attr('title', this.model.get('fileName'));
        },

        typeChange() {
            this.$('input.attachment-type').val(this.model.get('type'));
        },

        iconChange() {
            this.$('.attachment-item .fa').addClass(this.model.get('icon'));
        }
    });

    return EmailAttachmentView;
});
