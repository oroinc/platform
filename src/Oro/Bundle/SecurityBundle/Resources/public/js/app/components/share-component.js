define(function(require) {
    'use strict';

    var ShareComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShareView = require('orosecurity/js/app/views/share-view');
    require('jquery.select2');

    /**
     * @exports ShareComponent
     */
    ShareComponent = BaseComponent.extend({
        selectElSelector: '.select2.select2-offscreen',
        shareView: null,

        initialize: function(options) {
            this.options = options;
            this.init();
        },

        init: function() {
            this.initView();
            this.shareView.render();
            this._bindGridEvent();
        },

        initView: function() {
            this.shareView = new ShareView({
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
        _bindGridEvent: function() {
            var self = this;
            var gridWidgetName = this.options.gridWidgetName;
            if (!gridWidgetName) {
                return;
            }

            widgetManager.getWidgetInstanceByAlias(gridWidgetName, function(widget) {
                widget.on('grid-row-select', _.bind(self.onRowSelect, self, widget));
                widget.on('sharing-grid-submitted', _.bind(self.onGridAdd, self, widget));

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
            var targetClass = this.shareView.currentTargetClass();

            gridWidget._showLoading();
            var select2Data = $(this.selectElSelector).select2('data');
            var text = data.model.get('username') ? data.model.get('username') : data.model.get('name');
            select2Data = select2Data.concat({
                text: text,
                id: JSON.stringify({
                    'entityId': id,
                    'entityClass': targetClass
                })
            });
            $(this.selectElSelector).select2('data', select2Data);
            gridWidget._hideLoading();
            if (!dialogWidgetName) {
                return;
            }
            widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function(dialogWidget) {
                dialogWidget.remove();
            });
        },

        onGridAdd: function(gridWidget, data) {
            var dialogWidgetName = this.options.dialogWidgetName;
            var select2Data = $(this.selectElSelector).select2('data');
            var selected = {};
            gridWidget.pageComponent(this.shareView.currentGridName()).
                grid.collection.trigger('backgrid:getSelected', selected);
            var models = gridWidget.pageComponent(this.shareView.currentGridName()).grid.collection.models;
            gridWidget._showLoading();
            for(var key in models) {
                var model = models[key];
                if (selected.selected.indexOf(model.get('id')) !== -1) {
                    var text = model.get('username') ? model.get('username') : model.get('name');
                    select2Data = select2Data.concat({
                        text: text,
                        id: JSON.stringify({
                            'entityId': model.get('id'),
                            'entityClass': this.shareView.currentTargetClass()
                        })
                    });
                }
            }
            $(this.selectElSelector).select2('data', select2Data);
            gridWidget._hideLoading();
            if (!dialogWidgetName) {
                return;
            }
            widgetManager.getWidgetInstanceByAlias(dialogWidgetName, function(dialogWidget) {
                dialogWidget.remove();
            });
        }
    });

    return ShareComponent;
});
