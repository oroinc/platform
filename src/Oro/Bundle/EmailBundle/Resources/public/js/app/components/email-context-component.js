/*global define*/
define(function (require) {
    'use strict';

    var EmailContextComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        widgetManager = require('oroui/js/widget-manager'),
        messenger = require('oroui/js/messenger'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        EmailContextView = require('oroemail/js/app/views/email-context-view');

    /**
     * @exports EmailContextComponent
     */
    EmailContextComponent = BaseComponent.extend({
        contextView: null,

        initialize: function(options) {
            this.options = options;
            this.init();
        },

        init: function() {
            this.initView();
            this.contextView.render();
            this._bindGridEvent();
        },

        initView: function() {
            this.contextView = new EmailContextView({
                items: this.options.items || [],
                el: this.options._sourceElement,
                params: this.options.params || [],
                dialogWidgetName: this.options.dialogWidgetName
            });
        },

        /**
         * Bind event handlers on grid widget
         * @protected
         */
        _bindGridEvent: function () {
            var self = this,
                gridWidgetName = this.options.gridWidgetName;
            if (!gridWidgetName) {
                return;
            }

            widgetManager.getWidgetInstanceByAlias(gridWidgetName, function(widget) {
                widget.on('grid-row-select', _.bind(self.onRowSelect, self, widget));
            });
        },

        /**
         * Handles row selection on a grid
         *
         * @param {} gridWidget
         * @param {} data
         */
        onRowSelect: function (gridWidget, data) {
            var id = data.model.get('id'),
                dialogWidgetName = this.options.dialogWidgetName,
                contextTargetClass = this.contextView.currentTargetClass();

            gridWidget._showLoading();
            $.ajax({
                url: this.options.apiUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    entityId: this.options.sourceEntityId,
                    targetClassName: contextTargetClass,
                    targetId: id
                }
            }).done(function (response) {
                messenger.notificationFlashMessage(response.status, response.message);
                mediator.trigger('widget_success:activity_list:item:update');
                mediator.trigger('widget:doRefresh:email-context-activity-list-widget');
            }).always(function () {
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

    return EmailContextComponent;
});
