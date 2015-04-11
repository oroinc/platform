/*global define*/
define([
        'jquery',
        'orotranslation/js/translator',
        'routing',
        'oroui/js/messenger',
        'oroui/js/app/views/base/view',
        'oroui/js/mediator',
        'oroemail/js/app/models/email-context-activity-collection'
    ], function ($, __, routing, messenger, BaseView, mediator, EmailContextActivityCollection) {
    'use strict';
    //debugger;

    var EmailContextActivityView;

    /**
     * @export oroemail/js/app/views/email-context-activisty-view
     */
    EmailContextActivityView = BaseView.extend({
        initialize: function(options) {
            //debugger;
            this.options = options;

            this.template = _.template($('#email-context-activity-list').html());
            this.$container = options.$container;
            this.collection = new EmailContextActivityCollection('oro_api_delete_email_association');
            this.initEvents();

            if (this.options.items) {
                for (var i in this.options.items) {
                    this.collection.add(this.options.items[i]);
                }
            }

            /**
            * on adding activity item listen to "widget:doRefresh:email-context-activity-list-widget"
            */
            mediator.on('widget:doRefresh:email-context-activity-list-widget', this.doRefresh, this );
            EmailContextActivityView.__super__.initialize.apply(this, arguments);
        },

        add: function(model) {
            this.collection.add(model);
        },

        doRefresh: function() {
            var self = this;
            var  url = routing.generate('oro_api_get_email_associations_data', {entityId: this.options.entityId });
            $.ajax({
                method: "GET",
                url: url,
                success:function(r) {
                    self.collection.reset();
                    self.collection.add(r);
                }
            });
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


            this.collection.on('reset', function(model) {
                $(self.$container.context).html('');
            });

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model,
                    inputName: self.inputName
                });

                var $view = $(view);
                $(self.$container.context).append($view);

                $view.find('i.icon-remove').click(function() {
                    model.destroy({
                        success: function(model, response) {
                            var statusNotFound = 'NOT_FOUND';
                            if (response.status != statusNotFound) {
                                var $view = $(self.$container.context).find('[data-cid="' + model.cid + '"]');
                                $view.remove();
                                self.render();
                            }

                            Messenger.notificationFlashMessage(response.status != statusNotFound ? 'success': 'error', __(response.message));
                        },
                        error: function(model, response) {
                            Messenger.notificationFlashMessage('error', response.status + '  ' + __(response.statusText));
                        }
                    });
                });
            });
        }
    });

    return EmailContextActivityView;
});
