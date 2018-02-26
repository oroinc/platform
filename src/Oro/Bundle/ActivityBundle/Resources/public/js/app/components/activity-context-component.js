define(function(require) {
    'use strict';

    var ActivityContextComponent;
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var widgetManager = require('oroui/js/widget-manager');
    var messenger = require('oroui/js/messenger');
    var mediator = require('oroui/js/mediator');
    var MultiGridComponent = require('orodatagrid/js/app/components/multi-grid-component');

    /**
     * @exports ActivityContextComponent
     */
    ActivityContextComponent = MultiGridComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ActivityContextComponent() {
            ActivityContextComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * Handles row selection on a grid
         *
         * @param {} gridWidget
         * @param {} data
         */
        onRowSelect: function(gridWidget, data) {
            var id = data.model.get('id');
            var dialogWidgetName = this.options.dialogWidgetName;
            var contextTargetClass = this.contextView.currentTargetClass();

            gridWidget._showLoading();
            $.ajax({
                url: routing.generate('oro_api_post_activity_relation', {
                    activity: this.options.sourceEntityClassAlias, id: this.options.sourceEntityId
                }),
                type: 'POST',
                dataType: 'json',
                data: {
                    targets: [{entity: contextTargetClass, id: id}]
                },
                errorHandlerMessage: __('oro.ui.item_add_error')
            }).done(function() {
                messenger.notificationFlashMessage('success', __('oro.activity.contexts.added'));
                mediator.trigger('widget_success:activity_list:item:update');
                mediator.trigger('widget:doRefresh:activity-context-activity-list-widget');
            }).always(function() {
                gridWidget._hideLoading();
                if (!dialogWidgetName) {
                    return;
                }
                widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function(dialogWidget) {
                    dialogWidget.remove();
                });
            });
        }
    });

    return ActivityContextComponent;
});
