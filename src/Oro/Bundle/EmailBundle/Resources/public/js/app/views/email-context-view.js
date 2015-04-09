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

            this.template = _.template($('#email-context-list').html());
            this.$container = options.$container;
            this.$container.html('');
            this.collection = new EmailContextCollection();
            this.initEvents();

            if (this.options.items) {
                for (var i in this.options.items) {
                    this.collection.add(this.options.items[i]);
                }
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

            this.collection.on('add', function(model) {
                console.log(model);
                var view = self.template({
                    entity: model,
                    inputName: self.inputName
                });

                var $view = $(view);
                $(self.$container.context).append($view);

                $view.find('i.icon-remove').click(function() {
                    self.collection.remove(model.cid);
                });
            });

            this.collection.on('remove', function(model) {
                var $view = $(self.$container.context).find('[data-cid="' + model.cid + '"]');
                $view.remove();
                self.render();
            });
        }
    });

    return EmailContextView;
});
