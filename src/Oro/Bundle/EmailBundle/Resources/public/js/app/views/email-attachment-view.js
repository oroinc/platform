/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentView,
        $ = require('jquery'),
        EmailAttachmentCollection = require('oroemail/js/app/models/email-attachment-collection'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailAttachmentView = BaseView.extend({
        initialize: function(options) {
            this.template = _.template($('#email-attachment-item').html());
            this.inputName = options.inputName;

            this.options = options;
            if (this.options.items) {
                this.collection = new EmailAttachmentCollection(this.options.items);
            } else {
                this.collection = new EmailAttachmentCollection();
            }

            this.initEvents();
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
                $view.hide();

                self.$el.append($view);

                if (!model.get('id')) {
                    var $input = $view.find('input[type="file"]');

                    $input.change(function() {
                        var value = $input.val().replace(/^.*[\\\/]/, ''); // extracting file basename

                        if (value) {
                            model.set('fileName', value);
                            $view.find('span.label').html(value);
                            $view.show();

                            self.render();

                            $view.find('i.icon-remove').click(function() {
                                self.collection.remove(model.cid);
                            });
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
