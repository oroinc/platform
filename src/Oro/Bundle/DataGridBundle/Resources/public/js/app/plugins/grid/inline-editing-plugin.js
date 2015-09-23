define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var RouteModel = require('oroui/js/app/models/route-model');
    require('orodatagrid/js/app/components/cell-popup-editor-component');
    require('orodatagrid/js/app/views/editor/text-editor-view');

    InlineEditingPlugin = BasePlugin.extend({

        /**
         * true if any cell is in edit mode
         *
         * @type {boolean}
         */
        editModeEnabled: false,

        /**
         * This component is used by default for inline editing
         */
        DEFAULT_COMPONENT: 'orodatagrid/js/app/components/cell-popup-editor-component',

        /**
         * This view is used by default for editing
         */
        DEFAULT_VIEW: 'orodatagrid/js/app/views/editor/text-editor-view',

        initialize: function() {
            this.listenTo(this.main, 'beforeParseOptions', this.onBeforeParseOptions);
            InlineEditingPlugin.__super__.initialize.apply(this, arguments);
        },

        enable: function() {
            this.listenTo(this.main, 'afterMakeCell', this.onAfterMakeCell);
            InlineEditingPlugin.__super__.enable.call(this);
        },

        onAfterMakeCell: function(row, cell) {
            var originalRender = cell.render;
            var _this = this;
            cell.render = function() {
                var result = originalRender.apply(this, arguments);
                if (this.column.get('editable')) {
                    this.$el.addClass('editable view-mode');
                    this.$el.append('<i class="icon-edit hide-text">Edit</i>');
                    this.$el.popover({
                        content: __('oro.datagrid.inlineEditing.helpMessage'),
                        container: document.body,
                        placement: 'bottom',
                        delay: {show: 1400, hide: 0},
                        trigger: 'hover',
                        animation: false
                    });
                }
                return result;
            };
            function enterEditModeIfNeeded(e) {
                if (_this.column.get('editable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    _this.enterEditMode(cell);
                }
            }
            cell.events = _.extend({}, cell.events, {
                'dblclick': enterEditModeIfNeeded,
                'click .icon-edit': enterEditModeIfNeeded
            });

            delete cell.events.click;
        },

        /**
         * Copies validation options to columns from metadata
         *
         * @param options
         */
        onBeforeParseOptions: function(options) {
            var columnsMetadata = options.metadata.columns;
            var columns = options.columns;
            var preloadList = [];
            for (var i = 0; i < columns.length; i++) {
                var column = columns[i];
                var columnMetadata = _.findWhere(columnsMetadata, {name: column.name});
                if (columnMetadata) {
                    column.validationRules = columnMetadata.validation_rules;
                    if (columnMetadata.cell_editor) {
                        column.cellEditorComponent = columnMetadata.cell_editor.component ||
                            this.DEFAULT_COMPONENT;
                        column.cellEditorView = columnMetadata.cell_editor.view ||
                            this.DEFAULT_VIEW;
                        column.cellEditorOptions = columnMetadata.cell_editor.options || {};
                        preloadList.push(column.cellEditorComponent);
                        preloadList.push(column.cellEditorView);
                    } else {
                        column.cellEditorComponent = this.DEFAULT_COMPONENT;
                        column.cellEditorView = this.DEFAULT_VIEW;
                        column.cellEditorOptions = {};
                    }
                }
            }

            require(_.uniq(preloadList), _.noop, this._onRequireJsError);

            // initialize route which will be used for saving
            this.route = new RouteModel({
                routeName: options.metadata.inline_editing.route_name
            });
            this.httpMethod = options.metadata.inline_editing.http_method || 'PATCH';
        },

        enterEditMode: function(cell, fromPreviousCell) {
            if (this.editModeEnabled) {
                this.exitEditMode();
            }
            this.editModeEnabled = true;
            this.currentCell = cell;
            var _this = this;
            this.main.ensureCellIsVisible(cell);
            cell.$el.parent('tr:first').addClass('row-edit-mode');
            cell.$el.removeClass('view-mode');
            cell.$el.addClass('edit-mode');

            require([
                cell.column.get('cellEditorComponent'),
                cell.column.get('cellEditorView')
            ], function(CellEditorComponent, CellEditorView) {
                var editorComponent = new CellEditorComponent({
                    cell: cell,
                    view: CellEditorView,
                    viewOptions: cell.column.get('cellEditorOptions'),
                    fromPreviousCell: fromPreviousCell
                });

                _this.editorComponent = editorComponent;

                _this.listenTo(editorComponent, 'saveAction', _this.saveCurrentCell);
                _this.listenTo(editorComponent, 'cancelAction', _this.exitEditMode);
                _this.listenTo(editorComponent, 'saveAndEditNextAction', _this.saveCurrentCellAndEditNext);
                _this.listenTo(editorComponent, 'cancelAndEditNextAction', _this.editNextCell);
                _this.listenTo(editorComponent, 'saveAndEditPrevAction', _this.saveCurrentCellAndEditPrev);
                _this.listenTo(editorComponent, 'cancelAndEditPrevAction', _this.editPrevCell);
            });
        },

        saveCurrentCell: function(data, exit) {
            if (!this.editModeEnabled) {
                throw Error('Edit mode disabled');
            }
            var cell = this.currentCell;
            cell.$el.addClass('loading');
            var ctx = {
                cell: cell,
                oldValue: cell.model.get(cell.column.get('name'))
            };
            this.sendRequest(data, cell)
                .done(_.bind(InlineEditingPlugin.onSaveSuccess, ctx))
                .fail(_.bind(InlineEditingPlugin.onSaveError, ctx))
                .always(function() {
                    cell.$el.removeClass('loading');
                });
            if (exit !== false) {
                this.exitEditMode(cell);
            }
        },

        exitEditMode: function() {
            this.editModeEnabled = false;
            if (this.currentCell.$el) {
                this.currentCell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.currentCell.$el.addClass('view-mode');
                this.currentCell.$el.removeClass('edit-mode');
            }
            this.stopListening(this.editorComponent);
            this.editorComponent.dispose();
            delete this.dataModel;
            delete this.editorComponent;
        },

        editNextCell: function() {
            var nextCell = this.findNextEditableCell(this.currentCell);
            if (nextCell) {
                this.enterEditMode(nextCell);
            } else {
                this.exitEditMode();
            }
        },

        saveCurrentCellAndEditNext: function(data) {
            this.saveCurrentCell(data, false);
            this.editNextCell();
        },

        editPrevCell: function() {
            var nextCell = this.findPrevEditableCell(this.currentCell);
            if (nextCell) {
                this.enterEditMode(nextCell, true);
            } else {
                this.exitEditMode();
            }
        },

        saveCurrentCellAndEditPrev: function(data) {
            this.saveCurrentCell(data, false);
            this.editPrevCell();
        },

        sendRequest: function(data, cell) {
            cell.model.set(data);
            return $.ajax({
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                type: this.httpMethod,
                url: this.route.getUrl(cell.model.toJSON()),
                data: JSON.stringify(data)
            });
        },

        findNextEditableCell: function(cell) {
            function next(model) {
                var index = 1 + model.collection.indexOf(model);
                if (index < model.collection.length) {
                    return model.collection.at(index);
                }
                return null;
            }
            var row = cell.model;
            var column = cell.column;
            var columns = column.collection;
            do {
                column = next(column);
                while (column) {
                    if (column.get('editable')) {
                        return this.main.findCell(row, column);
                    }
                    column = next(column);
                }
                column = columns.at(0);
                row = next(row);
            } while (row);
            return null;
        },

        findPrevEditableCell: function(cell) {
            function prev(model) {
                var index = -1 + model.collection.indexOf(model);
                if (index >= 0) {
                    return model.collection.at(index);
                }
                return null;
            }
            var row = cell.model;
            var column = cell.column;
            var columns = column.collection;
            do {
                column = prev(column);
                while (column) {
                    if (column.get('editable')) {
                        return this.main.findCell(row, column);
                    }
                    column = prev(column);
                }
                column = columns.last();
                row = prev(row);
            } while (row);
            return null;
        },

        _onRequireJsError: function() {
            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.inlineEditing.loadingError'));
        }
    }, {
        onSaveSuccess: function() {
            if (!this.cell.disposed && this.cell.$el) {
                var _this = this;
                this.cell.$el.addClass('save-success');
                _.delay(function() {
                    _this.cell.$el.removeClass('save-success');
                }, 2000);
            }
            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.inlineEditing.successMessage'));
        },

        onSaveError: function() {
            if (!this.cell.disposed && this.cell.$el) {
                var _this = this;
                this.cell.$el.addClass('save-fail');
                _.delay(function() {
                    _this.cell.$el.removeClass('save-fail');
                }, 2000);
            }
            this.cell.model.set(this.cell.column.get('name'), this.oldValue);
            mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
        }
    });

    return InlineEditingPlugin;
});
