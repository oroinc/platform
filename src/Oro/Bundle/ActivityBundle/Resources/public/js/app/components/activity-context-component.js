define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const widgetManager = require('oroui/js/widget-manager');
    const messenger = require('oroui/js/messenger');
    const mediator = require('oroui/js/mediator');
    const MultiGridComponent = require('orodatagrid/js/app/components/multi-grid-component');

    /**
     * @exports ActivityContextComponent
     */
    const ActivityContextComponent = MultiGridComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ActivityContextComponent(options) {
            ActivityContextComponent.__super__.constructor.call(this, options);
        },

        /**
         * Handles row selection on a grid
         *
         * @param {} gridWidget
         * @param {} data
         */
        onRowSelect: function(gridWidget, data) {
            const id = data.model.get('id');
            const dialogWidgetName = this.options.dialogWidgetName;
            const contextTargetClass = this.contextView.currentTargetClass();

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
