import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import routing from 'routing';
import widgetManager from 'oroui/js/widget-manager';
import messenger from 'oroui/js/messenger';
import mediator from 'oroui/js/mediator';
import MultiGridComponent from 'orodatagrid/js/app/components/multi-grid-component';

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

export default ActivityContextComponent;
