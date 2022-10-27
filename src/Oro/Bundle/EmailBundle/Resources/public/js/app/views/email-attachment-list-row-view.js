define(function(require) {
    'use strict';

    const $ = require('jquery');
    const datetime = require('orolocale/js/formatter/datetime');
    const numeral = require('numeral');
    const EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!oroemail/templates/email-attachment/email-attachment-list-row-view.html');

    const EmailAttachmentListRowView = BaseView.extend({
        model: EmailAttachmentModel,

        events: {
            'click input[type="checkbox"]': 'checkboxClick'
        },

        listen: {
            'change:visible model': 'visibilityChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentListRowView(options) {
            EmailAttachmentListRowView.__super__.constructor.call(this, options);
        },

        render: function() {
            EmailAttachmentListRowView.__super__.render.call(this);

            this.$el.attr('data-type', this.model.get('type'));
            this.$el.find('[data-toggle="popover"]').popover({
                html: true,
                delay: {show: 300, hide: 100} // delay for image loading
            });
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = template;
            }

            return EmailAttachmentListRowView.__super__.getTemplateFunction.call(this);
        },

        getTemplateData: function() {
            const data = EmailAttachmentListRowView.__super__.getTemplateData.call(this);
            if ('fileName' in data && data.fileName.length > 15) {
                data.fileName = data.fileName.substr(0, 7) + '..' + data.fileName.substr(data.fileName.length - 7);
            }
            if ('fileSize' in data) {
                data.fileSize = numeral(data.fileSize).format('0.00b');
            }
            if ('modified' in data) {
                data.modified = datetime.formatDateTime(data.modified.date);
            }
            return data;
        },

        checkboxClick: function(event) {
            this.model.set('checked', $(event.target).prop('checked'));
        },

        visibilityChange: function() {
            this.render();
        }
    });

    return EmailAttachmentListRowView;
});
