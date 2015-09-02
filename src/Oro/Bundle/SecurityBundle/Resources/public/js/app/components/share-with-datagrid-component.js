define(function(require) {
    'use strict';

    var ShareWithDatagridComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShareWithDatagridView = require('orosecurity/js/app/views/share-with-datagrid-view');
    var helper = require('orosecurity/js/app/helper/component-helper');
    require('jquery.select2');

    /**
     * @exports ShareWithDatagridComponent
     */
    ShareWithDatagridComponent = BaseComponent.extend({
        shareWithDatagridView: null,

        initialize: function (options) {
            this.options = options;
            this.init();
        },

        init: function () {
            this.initView();
            this.shareWithDatagridView.render();
            this._bindGridEvent();
        },

        initView: function () {
            this.shareWithDatagridView = new ShareWithDatagridView({
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
            var self = this;
            var gridWidgetName = this.options.gridWidgetName;
            if (!gridWidgetName) {
                return;
            }

            widgetManager.getWidgetInstanceByAlias(gridWidgetName, function (widget) {
                widget.on('grid-row-select', _.bind(self.onRowSelect, self, widget));
                widget.on('share-with-datagrid-submitted', _.bind(self.onGridAdd, self, widget));

            });
        },

        /**
         * Handles row selection on a grid
         *
         * @param {} gridWidget
         * @param {} data
         */
        onRowSelect: function (gridWidget, data) {
            var id = data.model.get('id');
            var dialogWidgetName = this.options.dialogWidgetName;
            var targetClass = this.shareWithDatagridView.currentTargetClass();

            gridWidget._showLoading();
            var text = data.model.get('username') ? data.model.get('username') : data.model.get('name');
            var t = {
                text: text,
                id: JSON.stringify({
                    entityId: id,
                    entityClass: targetClass
                })
            };
            mediator.trigger('datagrid:shared-datagrid:add:data', {
                entityClass: targetClass,
                models: [{
                    id: id,
                    name: text,
                    entityClass: targetClass
                }]
            });
            gridWidget._hideLoading();
            if (!dialogWidgetName) {
                return;
            }
            widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function (dialogWidget) {
                dialogWidget.remove();
            });
        },

        /**
         * Handels rows selection on a grid
         *
         * @param gridWidget
         * @param data
         */
        onGridAdd: function (gridWidget, data) {
            var dialogWidgetName = this.options.dialogWidgetName;
            var selected = {};
            var grid = gridWidget.pageComponent(this.shareWithDatagridView.currentGridName()).grid;
            grid.collection.trigger('backgrid:getSelected', selected);
            var models = helper.extractModelsFromGridCollection(grid);
            gridWidget._hideLoading();
            mediator.trigger('datagrid:shared-datagrid:add:data', {
                entityClass: this.shareWithDatagridView.currentTargetClass(),
                models: models
            });
            if (!dialogWidgetName) {
                return;
            }
            widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function (dialogWidget) {
                dialogWidget.remove();
            });
        }
    });

    return ShareWithDatagridComponent;
});
