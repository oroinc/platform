/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentView,
        $ = require('jquery'),
        EmailAttachmentCollection = require('oroemail/js/app/models/email-attachment-collection'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        initialize: function(options) {
            this.options = options;
            this.template = _.template($('#email-attachment-item').html());
            this.inputName = options.inputName;

            this.collection = new EmailAttachmentCollection();
            this.initEvents();

            if (this.options.items) {
                this.collection.add(this.options.items);
            }
        },

        add: function(model) {
            this.collection.add(model);
        },

        render: function() {
            if (this.collection.models.length == 0) {
                this.$el.append($('<i class="no-attachments">No attachments</i>')); // todo translate
            }
        },

        initEvents: function() {
            var self = this;

            this.collection.on('add', function(model) {
                self.$el.find('i.no-attachments').remove();

                var view = self.template({
                    entity: model,
                    inputName: self.inputName
                });
                var $view = $(view);
                self.$el.append($view);
                $view.find('i.icon-remove').click(function() {
                    self.collection.remove(model.cid);
                });
                var $input = $view.find('input[type="file"]');
                $input.prop('disabled', true);

                if (!model.get('id')) {
                    $view.hide();
                    $input.prop('disabled', false);

                    $input.change(function() {
                        var value = $input.val().replace(/^.*[\\\/]/, ''); // extracting file basename

                        if (value) {
                            model.set('fileName', value);
                            $view.find('span.label').html(value);
                            $view.show();
                            $input.prop('disabled', true);

                            self.render();
                        } else {
                            self.collection.remove(model.cid);
                        }
                    });

                    $input.click();
                }
            });

            this.collection.on('remove', function(model) {
                var $view = self.$el.find('[data-cid="' + model.cid + '"]');
                $view.remove();
                self.render();
            });
        }
    });

    return EmailAttachmentView;
});
