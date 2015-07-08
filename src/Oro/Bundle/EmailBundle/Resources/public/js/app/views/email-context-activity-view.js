define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'routing',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator',
    'oroemail/js/app/models/email-context-activity-collection'
], function($, _, __, routing, messenger, BaseView, mediator, EmailContextActivityCollection) {
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
            this.$containerContextTargets = $(options.$container.context).find('.email-context-activity-items');
            this.collection = new EmailContextActivityCollection('oro_api_delete_activity_relation');
            this.initEvents();

            if (this.options.contextTargets) {
                this.collection.reset();
                for (var i in this.options.contextTargets) {
                    if (this.options.contextTargets.hasOwnProperty(i)) {
                        this.collection.add(this.options.contextTargets[i]);
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
            var  url = routing.generate('oro_api_get_email_context', {id: this.options.entityId});
            $.ajax({
                method: 'GET',
                url: url,
                success: function(r) {
                    self.collection.reset();
                    self.collection.add(r);
                    self.render();
                }
            });
        },

        render: function() {
            if (this.collection.length === 0) {
                this.$el.hide();
            } else {
                this.$el.show();
            }
        },

        initEvents: function() {
            var self = this;

            this.collection.on('reset', function() {
                self.$containerContextTargets.html('');
            });

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model,
                    inputName: self.inputName
                });

                var $view = $(view);
                self.$containerContextTargets.append($view);

                $view.find('i.icon-remove').click(function() {
                    model.destroy({
                        success: function(model, response) {
                            messenger.notificationFlashMessage('success', __('oro.email.contexts.removed'));

                            if (self.options.target &&
                                model.get('targetClassName') === self.options.target.className &&
                                model.get('targetId') === self.options.target.id) {
                                mediator.trigger('widget_success:activity_list:item:update');
                            } else {
                                mediator.trigger('widget:doRefresh:email-context-activity-list-widget');
                            }
                        },
                        error: function(model, response) {
                            messenger.showErrorMessage(__('oro.ui.item_delete_error'), response.responseJSON || {});
                        }
                    });
                });
            });
        }
    });

    return EmailContextActivityView;
});
