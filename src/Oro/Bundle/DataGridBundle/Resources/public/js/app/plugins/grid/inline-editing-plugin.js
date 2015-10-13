define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var CellIterator = require('../../../datagrid/cell-iterator');
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

        /**
         * This view is used by default for editing
         */
        DEFAULT_COLUMN_TYPE: 'text',

        enable: function() {
            this.listenTo(this.main, 'afterMakeCell', this.onAfterMakeCell);
            if (!this.options.metadata.inline_editing.save_api_accessor) {
                throw new Error('"save_api_accessor" option is required');
            }
            var ApiAccesor = this.options.metadata.inline_editing.save_api_accessor['class'];
            this.saveApiAccessor = new ApiAccesor(
                _.omit(this.options.metadata.inline_editing.save_api_accessor, 'class'));
            this.main.body.refresh();
            InlineEditingPlugin.__super__.enable.call(this);
        },

        disable: function() {
            InlineEditingPlugin.__super__.enable.call(this);
            this.main.body.refresh();
        },

        onAfterMakeCell: function(row, cell) {
            var originalRender = cell.render;
            var _this = this;
            cell.render = function() {
                originalRender.apply(this, arguments);
                if (_this.isEditable(cell)) {
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
                return this;
            };
            function enterEditModeIfNeeded(e) {
                if (_this.isEditable(cell)) {
                    _this.enterEditMode(cell);
                }
                e.preventDefault();
                e.stopPropagation();
            }
            cell.events = _.extend({}, cell.events, {
                'dblclick': enterEditModeIfNeeded,
                'click .icon-edit': enterEditModeIfNeeded
            });

            delete cell.events.click;
        },

        isEditable: function(cell) {
            var columnMetadata = cell.column.get('metadata');
            if (!columnMetadata) {
                return false;
            }
            switch (this.options.metadata.inline_editing.behaviour) {
                case 'enable_all':
                    if (columnMetadata.inline_editing && columnMetadata.inline_editing.enable === false) {
                        return false;
                    }
                    return (columnMetadata.type || this.DEFAULT_COLUMN_TYPE) in
                        this.options.metadata.inline_editing.default_editors;
                case 'enable_selected':
                    if (columnMetadata.inline_editing && columnMetadata.inline_editing.enable === true) {
                        return true;
                    }
                    break;
                default:
                    throw new Error('Unknown behaviour');
            }
            return false;
        },

        getCellEditorOptions: function(cell) {
            var columnMetadata = cell.column.get('metadata');
            var editor = (columnMetadata && columnMetadata.inline_editing && columnMetadata.inline_editing.editor) ?
                $.extend(true, {}, columnMetadata.inline_editing.editor) :
                {};

            if (!editor.component) {
                editor.component = this.options.metadata.inline_editing.cell_editor.component;
            }
            if (!editor.view) {
                editor.view = this.options.metadata.inline_editing
                    .default_editors[(columnMetadata.type || this.DEFAULT_COLUMN_TYPE)];
            }
            if (!editor.component_options) {
                editor.component_options = {};
            }

            if (columnMetadata && columnMetadata.inline_editing && columnMetadata.inline_editing.save_api_accessor) {
                var saveApiOptions = _.extend({}, this.options.metadata.inline_editing.save_api_accessor,
                    columnMetadata.inline_editing.save_api_accessor);
                var ApiAccesor = saveApiOptions.class;
                editor.save_api_accessor = new ApiAccesor(_.omit(saveApiOptions, 'class'));
            } else {
                // use main
                editor.save_api_accessor = this.saveApiAccessor;
            }

            editor.validation_rules = (columnMetadata.inline_editing &&
                columnMetadata.inline_editing.validation_rules) ?
                columnMetadata.inline_editing.validation_rules :
                {};

            return editor;
        },

        enterEditMode: function(cell, fromPreviousCell) {
            if (this.editModeEnabled) {
                this.exitEditMode();
            }
            this.editModeEnabled = true;
            this.currentCell = cell;
            this.cellIterator = new CellIterator(this.main, this.currentCell);
            this.main.ensureCellIsVisible(cell);
            cell.$el.parent('tr:first').addClass('row-edit-mode');
            cell.$el.removeClass('view-mode');
            cell.$el.addClass('edit-mode');

            this.toggleHeaderCellHighlight(cell, true);

            var editor = this.getCellEditorOptions(cell);
            this.editor = editor;

            var CellEditorComponent = editor.component;
            var CellEditorView = editor.view;

            var classNames = this.buildClassNames(editor, cell);
            var editorComponent = new CellEditorComponent(_.extend({}, editor.component_options, {
                cell: cell,
                view: CellEditorView,
                viewOptions: $.extend(true, {
                    validationRules: editor.validation_rules
                }, editor.view_options || {}, {
                    className: classNames.join(' ')
                }),
                fromPreviousCell: fromPreviousCell
            }));

            editorComponent.view.focus(!!fromPreviousCell);

            this.editorComponent = editorComponent;

            this.listenTo(editorComponent, 'saveAction', this.saveCurrentCell);
            this.listenTo(editorComponent, 'cancelAction', this.exitEditMode);
            this.listenTo(editorComponent, 'saveAndEditNextAction', this.saveCurrentCellAndEditNext);
            this.listenTo(editorComponent, 'cancelAndEditNextAction', this.editNextCell);
            this.listenTo(editorComponent, 'saveAndEditPrevAction', this.saveCurrentCellAndEditPrev);
            this.listenTo(editorComponent, 'cancelAndEditPrevAction', this.editPrevCell);
        },

        toggleHeaderCellHighlight: function(cell, state) {
            var columnIndex = this.main.columns.indexOf(cell.column);
            this.main.header.row.cells[columnIndex].$el.toggleClass('header-cell-highlight', state);
        },

        buildClassNames: function(editor, cell) {
            var classNames = [];
            if (editor.view_options && editor.view_options.css_class_name) {
                classNames.push(editor.view_options.css_class_name);
            }
            if (cell.column.get('name')) {
                classNames.push(cell.column.get('name') + '-column-editor');
            }

            if (this.main.name) {
                classNames.push(this.main.name + '-grid-editor');
            }

            if (cell.column.get('metadata') && cell.column.get('metadata').type) {
                classNames.push(cell.column.get('metadata').type + '-frontend-type-editor');
            }
            return classNames;
        },

        saveCurrentCell: function(exit) {
            if (!this.editModeEnabled) {
                throw Error('Edit mode disabled');
            }
            var cell = this.currentCell;
            var serverUpdateData = this.editorComponent.view.getServerUpdateData();
            var modelUpdateData = this.editorComponent.view.getModelUpdateData();
            cell.$el.addClass('loading');
            var ctx = {
                main: this.main,
                cell: cell,
                oldState: _.pick(cell.model.toJSON(), _.keys(modelUpdateData))
            };
            cell.model.set(modelUpdateData);
            this.main.trigger('content:update');
            if (this.editor.save_api_accessor.initialOptions.field_name) {
                var keys = _.keys(serverUpdateData);
                if (keys.length > 1) {
                    throw new Error('Only single field editors are supported with field_name option');
                }
                var newData = {};
                newData[this.editor.save_api_accessor.initialOptions.field_name] = serverUpdateData[keys[0]];
                serverUpdateData = newData;
            }
            this.editor.save_api_accessor.send(cell.model.toJSON(), serverUpdateData)
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
                this.toggleHeaderCellHighlight(this.currentCell, false);
                this.currentCell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.currentCell.$el.addClass('view-mode');
                this.currentCell.$el.removeClass('edit-mode');
            }
            this.stopListening(this.editorComponent);
            this.editorComponent.dispose();
            delete this.editorComponent;
        },

        editNextCell: function() {
            var _this = this;
            function checkEditable(cell) {
                if (!_this.isEditable(cell)) {
                    return _this.cellIterator.next().then(checkEditable);
                }
                return cell;
            }
            this.cellIterator.next().then(checkEditable).done(function(cell) {
                _this.enterEditMode(cell);
            }).fail(function() {
                _this.exitEditMode();
            });
        },

        editPrevCell: function() {
            var _this = this;
            function checkEditable(cell) {
                if (!_this.isEditable(cell)) {
                    return _this.cellIterator.prev().then(checkEditable);
                }
                return cell;
            }
            this.cellIterator.prev().then(checkEditable).done(function(cell) {
                _this.enterEditMode(cell);
            }).fail(function() {
                _this.exitEditMode();
            });
        },

        saveCurrentCellAndEditNext: function(data) {
            this.saveCurrentCell(false);
            this.editNextCell();
        },

        saveCurrentCellAndEditPrev: function(data) {
            this.saveCurrentCell(false);
            this.editPrevCell();
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
            this.cell.model.set(this.oldState);
            this.main.trigger('content:update');

            // @TODO update message
            mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
        }
    });

    return InlineEditingPlugin;
});
