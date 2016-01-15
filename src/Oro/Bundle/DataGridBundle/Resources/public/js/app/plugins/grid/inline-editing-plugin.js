define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var CellIterator = require('../../../datagrid/cell-iterator');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    require('orodatagrid/js/app/components/cell-popup-editor-component');
    require('oroform/js/app/views/editor/text-editor-view');

    InlineEditingPlugin = BasePlugin.extend({
        /**
         * This component is used by default for inline editing
         */
        DEFAULT_COMPONENT: 'orodatagrid/js/app/components/cell-popup-editor-component',

        /**
         * This view is used by default for editing
         */
        DEFAULT_VIEW: 'oroform/js/app/views/editor/text-editor-view',

        /**
         * This view is used by default for editing
         */
        DEFAULT_COLUMN_TYPE: 'string',

        /**
         * Help message for cells
         */
        helpMessage: __('oro.form.inlineEditing.helpMessage'),

        /**
         * Active editors set
         */
        activeEditors: null,

        initialize: function(main, options) {
            this.activeEditorComponents = [];
            InlineEditingPlugin.__super__.initialize.apply(this, arguments);
        },

        enable: function() {
            this.main.$el.addClass('grid-editable');
            this.listenTo(this.main, {
                afterMakeCell: this.onAfterMakeCell
            });
            this.listenTo(mediator, 'page:beforeChange', function() {
                this.removeActiveEditorComponents();
            });
            if (!this.options.metadata.inline_editing.save_api_accessor) {
                throw new Error('"save_api_accessor" option is required');
            }
            var ConcreteApiAccessor = this.options.metadata.inline_editing.save_api_accessor['class'];
            this.saveApiAccessor = new ConcreteApiAccessor(
                _.omit(this.options.metadata.inline_editing.save_api_accessor, 'class'));
            this.main.body.refresh();
            InlineEditingPlugin.__super__.enable.call(this);
        },

        disable: function() {
            this.removeActiveEditorComponents();
            this.main.$el.removeClass('grid-editable');
            this.main.body.refresh();
            InlineEditingPlugin.__super__.disable.call(this);
        },

        onAfterMakeCell: function(row, cell) {
            var _this = this;
            function enterEditModeIfNeeded(e) {
                if (_this.isEditable(cell)) {
                    _this.enterEditMode(cell);
                }
                e.preventDefault();
                e.stopPropagation();
            }
            var originalRender = cell.render;
            cell.render = function() {
                var cell = this;
                originalRender.apply(cell, arguments);
                var originalEvents = cell.events;
                if (_this.isEditable(cell)) {
                    cell.$el.addClass('editable view-mode prevent-text-selection-on-dblclick');
                    cell.$el.append('<i data-role="edit" ' +
                        'class="icon-pencil skip-row-click hide-text inline-editor__edit-action"' +
                        'title="' + __('Edit') + '">' + __('Edit') + '</i>');
                    cell.$el.attr('title', _this.helpMessage);
                    cell.events = _.extend(Object.create(cell.events), {
                        'dblclick': enterEditModeIfNeeded,
                        'mousedown [data-role=edit]': enterEditModeIfNeeded,
                        'click': _.noop
                    });
                }
                cell.delegateEvents();
                cell.events = originalEvents;
                return cell;
            };
        },

        isEditable: function(cell) {
            var columnMetadata = cell.column.get('metadata');
            if (!columnMetadata) {
                return false;
            }
            var editable;
            var enableConfigValue = columnMetadata.inline_editing && columnMetadata.inline_editing.enable;
            // validateUrlParameters
            switch (this.options.metadata.inline_editing.behaviour) {
                case 'enable_all':
                    if (enableConfigValue !== false) {
                        editable = (columnMetadata.inline_editing && columnMetadata.inline_editing.enable === true) ||
                            (columnMetadata.type || this.DEFAULT_COLUMN_TYPE) in
                                this.options.metadata.inline_editing.default_editors;
                    } else {
                        editable = false;
                    }
                    break;
                case 'enable_selected':
                    editable = enableConfigValue === true;
                    break;
                default:
                    throw new Error('Unknown behaviour');
            }
            return editable ?
                this.getCellEditorOptions(cell).save_api_accessor.validateUrlParameters(cell.model.toJSON()) :
                false;
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
                if (!(columnMetadata.inline_editing.save_api_accessor instanceof ApiAccessor)) {
                    var saveApiOptions = _.extend({}, this.options.metadata.inline_editing.save_api_accessor,
                        columnMetadata.inline_editing.save_api_accessor);
                    var ConcreteApiAccessor = saveApiOptions.class;
                    columnMetadata.inline_editing.save_api_accessor = new ConcreteApiAccessor(
                        _.omit(saveApiOptions, 'class'));
                }
                editor.save_api_accessor = columnMetadata.inline_editing.save_api_accessor;
            } else {
                // use main
                editor.save_api_accessor = this.saveApiAccessor;
            }

            var validationRules = (columnMetadata.inline_editing &&
                columnMetadata.inline_editing.validation_rules) ?
                columnMetadata.inline_editing.validation_rules :
                {};

            _.each(validationRules, function(params, ruleName) {
                // normalize rule's params, in case is it was defined as 'NotBlank: ~'
                validationRules[ruleName] = params || {};
            });

            editor.viewOptions = $.extend(true, {}, editor.view_options || {}, {
                className: this.buildClassNames(editor, cell).join(' '),
                validationRules: validationRules
            });

            return editor;
        },

        getOpenedEditor: function(cell) {
            return _.find(this.activeEditorComponents, function(editor) {
                return editor.options.cell === cell;
            });
        },

        enterEditMode: function(cell, fromPreviousCell) {
            var existingEditorComponent = this.getOpenedEditor(cell);
            if (existingEditorComponent) {
                existingEditorComponent.view.focus(!!fromPreviousCell);
                return;
            }
            this.main.ensureCellIsVisible(cell);

            var editor = this.getCellEditorOptions(cell);
            var CellEditorComponent = editor.component;
            var CellEditorView = editor.view;

            if (!CellEditorView) {
                throw new Error('Editor view in not available for `' + cell.column.get('name') + '` column');
            }

            var editorComponent = new CellEditorComponent(_.extend({}, editor.component_options, {
                cell: cell,
                view: CellEditorView,
                viewOptions: editor.viewOptions,
                save_api_accessor: editor.save_api_accessor,
                grid: this.main,
                plugin: this
            }));

            editorComponent.view.on('focus', function() {
                this.highlightCell(cell, true);
            }, this);

            editorComponent.view.on('blur', function() {
                this.highlightCell(cell, false);
            }, this);
            editorComponent.view.focus(!!fromPreviousCell);

            this.activeEditorComponents.push(editorComponent);

            editorComponent.on('dispose', function() {
                var index = this.activeEditorComponents.indexOf(editorComponent);
                if (index !== -1) {
                    this.activeEditorComponents.splice(index, 1);
                }
            }, this);
        },

        buildClassNames: function(editor, cell) {
            var classNames = ['skip-row-click'];
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

        editNextCell: function(cell) {
            this.editCellByIteratorMethod('next', cell);
        },

        editNextRowCell: function(cell) {
            this.editCellByIteratorMethod('nextRow', cell);
        },

        editPrevCell: function(cell) {
            this.editCellByIteratorMethod('prev', cell);
        },

        editPrevRowCell: function(cell) {
            this.editCellByIteratorMethod('prevRow', cell);
        },

        editCellByIteratorMethod: function(iteratorMethod, cell) {
            var _this = this;
            var fromPreviousCell = iteratorMethod === 'prev';
            this.trigger('lockUserActions', true);
            var cellIterator = new CellIterator(this.main, cell);
            function checkEditable(cell) {
                if (!_this.isEditable(cell)) {
                    return cellIterator[iteratorMethod]().then(checkEditable);
                }
                return cell;
            }
            cellIterator[iteratorMethod]().then(checkEditable).done(function(cell) {
                _this.enterEditMode(cell, fromPreviousCell);
                _this.trigger('lockUserActions', false);
            }).fail(function() {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                _this.trigger('lockUserActions', false);
            });
        },

        removeActiveEditorComponents: function() {
            for (var i = 0; i < this.activeEditorComponents.length; i++) {
                this.activeEditorComponents[i].dispose();
            }
            this.activeEditorComponents = [];
        },

        lastHighlightedCell: null,

        highlightCell: function(cell, highlight) {
            highlight = highlight !== false;
            if (this.lastHighlightedCell === cell && highlight) {
                return;
            }
            if (this.lastHighlightedCell !== cell && !highlight) {
                return;
            }
            if (this.lastHighlightedCell && highlight) {
                this.highlightCell(this.lastHighlightedCell, false);
            }
            this.toggleHeaderCellHighlight(cell, highlight);
            if (highlight) {
                cell.$el.parent('tr:first').addClass('row-edit-mode');
                this.lastHighlightedCell = cell;
            } else {
                cell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.lastHighlightedCell = null;
            }
        },

        toggleHeaderCellHighlight: function(cell, state) {
            var columnIndex = this.main.columns.indexOf(cell.column);
            var headerCell = this.main.findHeaderCellByIndex(columnIndex);
            if (headerCell) {
                headerCell.$el.toggleClass('header-cell-highlight', state);
            }
        }
    });

    return InlineEditingPlugin;
});
