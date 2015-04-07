/*global define*/
define(function (require) {
    'use strict';

    var EmailContextView,
        $ = require('jquery'),
        EmailContextCollection = require('oroemail/js/app/models/email-context-collection'),
        BaseView= require('oroui/js/app/views/base/view');

    EmailContextView = BaseView.extend({
        initialize: function(options) {
            this.options = options;

            this.template = _.template($('#email-context-item').html());
            //this.inputName = options.inputName;
            //this.$container = options.$container;
            //
            //this.$container.html('');

            this.collection = new EmailContextCollection();
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
                this.$el.hide();
            } else {
                this.$el.show();
            }
        },

        initEvents: function() {
            var self = this;
            var dropdown = this.$el.find('#context-items-dropdown');
            var firstItem = this.$el.find('#email-context-current-item');
            var dropdownButton = this.$el.find('#email-context-current-block');

            dropdownButton.bind('click', function()
            {
                dropdown.toggle('show');
            });

            this.collection.on('add', function(model) {

                var view = self.template({
                    entity: model
                });
                var $view = $(view);

                if (model.attributes.first) {
                    firstItem.html(model.attributes.label);
                }

                dropdown.append($view);

                //$view.find('i.icon-remove').click(function() {
                //    self.collection.remove(model.cid);
                //});
                //var $input = $view.find('input[type="file"]');
                //
                //if (!model.get('id')) {
                //    $view.hide();
                //
                //    $input.change(function() {
                //        var value = $input.val().replace(/^.*[\\\/]/, ''); // extracting file basename
                //
                //        if (value) {
                //            model.set('fileName', value);
                //            $view.find('span.filename span.filename-label').html(value);
                //            $view.show();
                //
                //            self.render();
                //        } else {
                //            self.collection.remove(model.cid);
                //        }
                //    });
                //
                //    $input.click();
                //}
            });

            //this.collection.on('remove', function(model) {
            //    var $view = self.$container.find('[data-cid="' + model.cid + '"]');
            //    $view.remove();
            //    self.render();
            //});
        }
    });

    return EmailContextView;
});
