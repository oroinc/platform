define(function(require) {
    'use strict';

    var EmailAttachmentListRowView;
    var $ = require('jquery');
    var datetime = require('orolocale/js/formatter/datetime');
    var numeral = require('numeral');
    var EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model');
    var BaseView = require('oroui/js/app/views/base/view');

    EmailAttachmentListRowView = BaseView.extend({
        model: EmailAttachmentModel,

        events: {
            'click input[type="checkbox"]': 'checkboxClick'
        },

        listen: {
            'change:visible model':         'visibilityChange'
        },

        render: function() {
            EmailAttachmentListRowView.__super__.render.call(this);

            this.$el.attr('data-type', this.model.get('type'));
            this.$el.find('[data-toggle="popover"]').popover({
                html: true,
                delay: {show: 300, hide: 100} //delay for image loading
            });
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-list-row-view').html();
            }

            return EmailAttachmentListRowView.__super__.getTemplateFunction.call(this);
        },

        getTemplateData: function() {
            var data = EmailAttachmentListRowView.__super__.getTemplateData.apply(this, arguments);
            if ('fileName' in data && data.fileName.length > 15) {
                data.fileName = data.fileName.substr(0, 7) + '..' + data.fileName.substr(data.fileName.length - 7);
            }
            if ('fileSize' in data) {
                data.fileSize = numeral(data.fileSize).format('b');
            }
            if ('modified' in data) {
                data.modified = datetime.formatDateTime(data.modified);
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
