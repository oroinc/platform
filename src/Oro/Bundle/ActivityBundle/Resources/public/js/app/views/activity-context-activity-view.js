define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'routing',
    'oroui/js/messenger',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator',
    'oroactivity/js/app/models/activity-context-activity-collection',
    'oroui/js/error'
], function($, _, __, routing, messenger, BaseView, mediator, ActivityContextActivityCollection, error) {
    'use strict';

    var ActivityContextActivityView;

    /**
     * @export oroactivity/js/app/views/activity-context-activity-view
     */
    ActivityContextActivityView = BaseView.extend({
        options: {},

        events: {},

        /**
         * @inheritDoc
         */
        constructor: function ActivityContextActivityView() {
            ActivityContextActivityView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.template = _.template($('#activity-context-activity-list').html());
            this.$containerContextTargets = $(options.el).find('.activity-context-activity-items');
            this.collection = new ActivityContextActivityCollection();
            this.editable = options.editable;
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
             * on adding activity item listen to "widget:doRefresh:activity-context-activity-list-widget"
             */
            this.listenTo(mediator, 'widget:doRefresh:activity-context-activity-list-widget', this.doRefresh, this);
            this.listenTo(mediator, 'widget:doRefresh:activity-thread-context', this.doRefresh, this);
            ActivityContextActivityView.__super__.initialize.apply(this, arguments);

            if (!this.options.contextTargets) {
                this.doRefresh();
            } else {
                this.render();
            }
        },

        add: function(model) {
            this.collection.add(model);
        },

        doRefresh: function() {
            var url = routing.generate('oro_api_get_activity_context', {
                activity: this.options.activityClass,
                id: this.options.entityId
            });
            var collection = this.collection;
            var self = this;
            $.ajax({
                method: 'GET',
                url: url,
                success: function(r) {
                    collection.reset();
                    collection.add(r);
                },
                complete: function() {
                    self.render();
                }
            });
        },

        render: function() {
            this.$el.toggle(this.collection.length > 0);

            this.trigger('render');
        },

        initEvents: function() {
            var self = this;

            this.collection.on('reset', function() {
                self.$containerContextTargets.html('');
            });

            this.collection.on('add', function(model) {
                var view = self.template({
                    entity: model,
                    inputName: self.inputName,
                    editable: self.editable
                });

                var $view = $(view);
                self.$containerContextTargets.append($view);

                $view.find('[data-role="delete-item"]').click(function() {
                    $view.fadeOut();
                    model.destroy({
                        success: function(model, response) {
                            messenger.notificationFlashMessage('success', __('oro.activity.contexts.removed'));

                            if (self.options.target &&
                                model.get('targetClassName') === self.options.target.className &&
                                model.get('targetId') === self.options.target.id) {
                                mediator.trigger('widget_success:activity_list:item:update');
                            } else {
                                mediator.trigger('widget:doRefresh:activity-context-activity-list-widget');
                            }
                        },
                        errorHandlerMessage: __('oro.ui.item_delete_error'),
                        error: function(model, response) {
                            $view.show();
                        }
                    });
                });
            });
        }
    });

    return ActivityContextActivityView;
});
