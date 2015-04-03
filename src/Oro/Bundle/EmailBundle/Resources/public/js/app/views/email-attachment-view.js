/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentView,
        $ = require('jquery'),
        _ = require('underscore'),
        EmailAttachmentModel = require('oroemail/js/app/models/email-attachment-model'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        model: EmailAttachmentModel,
        inputName: '',

        events: {
            'click i.icon-remove': 'removeClick'
        },

        listen: {
            'change:fileName model': 'fileNameChange'
        },

        getTemplateFunction: function() {
            if (!this.template) {
                this.template = $('#email-attachment-item').html();
            }

            return EmailAttachmentView.__super__.getTemplateFunction.call(this);
        },

        getTemplateData: function() {
            return {
                entity: this.model,
                inputName: this.inputName
            };
        },

        removeClick: function() {
            this.model.trigger('destroy', this.model, this);
        },

        fileSelect: function() {
            var self = this;
            var $fileInput = this.$el.find('input[type="file"]');
            this.$el.hide();

            $fileInput.change(function() {
                var value = $fileInput.val().replace(/^.*[\\\/]/, '');

                if (value) {
                    self.model.set('fileName', value);
                    self.$el.show();
                } else {
                    self.collection.remove(self.model);
                }
            });
            $fileInput.click();
        },

        fileNameChange: function() {
            this.$el.find('span.filename-label').html(this.model.get('fileName'));
        }
    });

    return EmailAttachmentView;
});
