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

    var EmailContextActivityView;

    /**
     * @export oroemail/js/app/views/email-context-activity-view
     */
    EmailContextActivityView = BaseView.extend({
        options: {},
        events: {},

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.template = _.template($('#email-context-activity-list').html());
            this.$container = options.$container;
            this.$containerForItems = $(options.$container.context).find('.email-context-activity-items');
            this.collection = new EmailContextActivityCollection('oro_api_delete_email_association');
            this.initEvents();

            if (this.options.items) {
                this.collection.reset();
                for (var i in this.options.items) {
                    if (this.options.items.hasOwnProperty(i)) {
                        this.collection.add(this.options.items[i]);
                    }
                }
            }

            /**
            * on adding activity item listen to "widget:doRefresh:email-context-activity-list-widget"
            */
            this.listenTo(mediator, 'widget:doRefresh:email-context-activity-list-widget', this.doRefresh, this);
            this.listenTo(mediator, 'widget:doRefresh:email-thread-context', this.doRefresh, this);
            EmailContextActivityView.__super__.initialize.apply(this, arguments);
            this.render();
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
                    self.render();
                }
            });
        },

        render: function() {
            if (this.collection.length == 0) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
        },

        initEvents: function() {
            var self = this;

            this.collection.on('reset', function() {
                self.$containerForItems.html('');
            });

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model,
                    inputName: self.inputName
                });

                var $view = $(view);
                self.$containerForItems.append($view);

                $view.find('i.icon-remove').click(function() {
                    model.destroy({
                        success: function(model, response) {
                            if (response.status != 'success') {
                                var $view = self.$containerForItems.find('[data-cid="' + model.cid + '"]');
                                $view.remove();
                                self.render();
                            }
                            messenger.notificationFlashMessage(response.status, response.message);

                            if (model.get('targetClassName') == self.options.target.className &&
                                model.get('targetId') == self.options.target.id) {
                                mediator.trigger('widget_success:activity_list:item:update');
                            } else {
                                mediator.trigger('widget:doRefresh:email-context-activity-list-widget');
                            }
                        },
                        error: function(model, response) {
                            if (response.status == 'error') {
                                messenger.notificationFlashMessage('error', response.message);
                            } else {
                                messenger.notificationFlashMessage('error', response.status + '  ' + __(response.statusText));
                            }
                        }
                    });
                });
            });
        }
    });

    return EmailContextActivityView;
});
