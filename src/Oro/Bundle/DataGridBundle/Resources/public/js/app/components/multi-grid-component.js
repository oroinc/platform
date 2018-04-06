define(function(require) {
    'use strict';

    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var MultiGridView = require('orodatagrid/js/app/views/multi-grid-view');

    /**
     * @exports MultiGridComponent
     */
    var MultiGridComponent = BaseComponent.extend({
        /**
         * keys
         * - dialogWidgetName: name of the widget
         * - gridWidgetName: name of the grid widget
         * - items: array of objects with keys
         *      - label
         *      - gridName
         *      - className
         * - params: object with keys
         *      - grid_query: grid parameters
         */
        options: {},

        contextView: null,

        /**
         * @inheritDoc
         */
        constructor: function MultiGridComponent() {
            MultiGridComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = options;
            this.initView();
            this.contextView.render();
            this._bindGridEvent();
        },

        initView: function() {
            var params = this.options.params || {};
            var routeParams = {};

            widgetManager.getWidgetInstanceByAlias(this.options.dialogWidgetName, function(widget) {
                routeParams = widget.options.routeParams || {};
            });
            if (!_.isEmpty(routeParams)) {
                params = _.extend({}, params, {routeParams: routeParams});
            }

            this.contextView = new MultiGridView({
                items: this.options.items || [],
                el: this.options._sourceElement,
                params: params,
                dialogWidgetName: this.options.dialogWidgetName,
                gridWidgetName: this.options.gridWidgetName
            });
        },

        /**
         * Bind event handlers on grid widget
         * @protected
         */
        _bindGridEvent: function() {
            var self = this;
            var gridWidgetName = this.options.gridWidgetName;
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
        onRowSelect: function(gridWidget, data) {
            var id = data.model.get('id');
            var dialogWidgetName = this.options.dialogWidgetName;
            var targetClass = this.contextView.currentTargetClass();

            mediator.trigger(
                dialogWidgetName + ':select',
                JSON.stringify({entityClass: targetClass, entityId: id})
            );

            widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function(dialogWidget) {
                dialogWidget.remove();
            });
        }
    });

    return MultiGridComponent;
});
