define(function(require) {
    'use strict';

    var SharedDatagridComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var messenger = require('oroui/js/messenger');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @exports SharedDatagridComponent
     */
    SharedDatagridComponent = BaseComponent.extend({
        initialize: function (options) {
            this.options = options;
            this.init();
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }

            mediator.off('datagrid:mass:frontend:execute:shared-datagrid');
            mediator.off('datagrid:frontend:execute:shared-datagrid');
            mediator.off('datagrid:shared-datagrid:add:data');
            mediator.off('datagrid:shared-datagrid:add:data-from-select2');
            mediator.off('widget:shared-dialog:apply');

            SharedDatagridComponent.__super__.dispose.call(this);
        },

        init: function () {
            this._bindGridEvent();
        },

        _bindGridEvent: function () {
            this._binMassActions();
            this._bindActions();
            this._bindSharedWithDatagridHandler();
            this._bindSelect2Handler();
            this._bindShareDialogHandler();
        },

        _binMassActions: function () {
            mediator.on('datagrid:mass:frontend:execute:shared-datagrid', function (action) {
                _.each(action.datagrid.getSelectionState().selectedModels, function (model) {
                    action.datagrid.collection.get(model).trigger('backgrid:select', model, false);
                    action.datagrid.removeRow(model, {silent: true});
                    _.each(action.datagrid.body.rows, function (row) {
                        if (row.model.id === model.id) {
                            row.$el.remove();
                        }
                    });
                });
                if (action.datagrid.collection.length == 0) {
                    action.datagrid.collection.reset([]);
                }
            });
        },

        _bindActions: function () {
            mediator.on('datagrid:frontend:execute:shared-datagrid', function (action) {
                action.datagrid.collection.get(action.model).trigger('backgrid:select', action.model, false);
                action.datagrid.removeRow(action.model, {silent: true});
                _.each(action.datagrid.body.rows, function (row) {
                    if (row.model.id === action.model.id) {
                        row.$el.remove();
                    }
                });
                if (action.datagrid.collection.length == 0) {
                    action.datagrid.collection.reset([]);
                }
            });
        },

        _bindSharedWithDatagridHandler: function () {
            mediator.on('datagrid:shared-datagrid:add:data', function (data) {
                widgetManager.getWidgetInstanceByAlias('shared-dialog', function (widget) {
                    var grid = widget.pageComponent('shared-datagrid').grid;
                    _.each(data.models, function (model) {
                        var id = JSON.stringify({
                            entityId: model.id,
                            entityClass: data.entityClass
                        });
                        if (grid.collection.where({id: id}).length == 0) {
                            var model = {
                                id: id,
                                entity: model.get('entity')
                            };
                            if (grid.collection.length == 0) {
                                grid.collection.reset([model]);
                            } else {
                                grid.collection.add(model);
                            }
                            grid.collection.get(model).trigger('backgrid:select', model, true);
                        }
                    });
                });
            });
        },

        _bindSelect2Handler: function () {
            mediator.on('datagrid:shared-datagrid:add:data-from-select2', function (data) {
                widgetManager.getWidgetInstanceByAlias('shared-dialog', function (widget) {
                    var grid = widget.pageComponent('shared-datagrid').grid;
                    if (grid.collection.where({id: data.id}).length == 0) {
                        var model = {
                            id: data.id,
                            entity: data.entity
                        };
                        if (grid.collection.length == 0) {
                            grid.collection.reset([model]);
                        } else {
                            grid.collection.add(model);
                        }
                        grid.collection.get(model).trigger('backgrid:select', model, true);
                    }
                });
            });
        },

        _bindShareDialogHandler: function () {
            var self = this;
            mediator.on('widget:shared-dialog:apply', function() {
                widgetManager.getWidgetInstanceByAlias('shared-dialog', function (widget) {
                    var grid = widget.pageComponent('shared-datagrid').grid;

                    var entitiesParam = [];
                    _.each(grid.getSelectionState().selectedModels, function (model) {
                        entitiesParam.push(model.id)
                    });
                    var finallyFunc = function () {
                        widgetManager.getWidgetInstanceByAlias('shared-dialog', function (widget) {
                            messenger.notificationFlashMessage('success', __('oro.security.action.shared'));
                            widget.remove();
                            mediator.execute('refreshPage');
                        });
                    };
                    grid.collection.sync('POST', grid.collection, {
                        method: 'POST',
                        url: routing.generate(
                            'oro_share_update',
                            {
                                'entityId': self.options.entityId,
                                '_widgetContainer': 'dialog'
                            }
                        ) + '&entityClass=' + self.options.entityClass,
                        contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                        data: 'oro_share_form%5BentityClass%5D=' + self.options.entityClass
                        + '&oro_share_form%5BentityId%5D=' + self.options.entityId
                        + '&oro_share_form%5Bentities%5D=' + encodeURIComponent(entitiesParam.join(';')),
                        wait: true,
                        error: finallyFunc,
                        success: finallyFunc
                    });
                });
            });
        }
    });

    return SharedDatagridComponent;
});
