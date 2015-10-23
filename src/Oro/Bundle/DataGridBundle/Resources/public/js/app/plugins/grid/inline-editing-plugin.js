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
    var backdropManager = require('oroui/js/tools/backdrop-manager');
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
        DEFAULT_COLUMN_TYPE: 'string',

        /**
         * If true interface should not respond to user actions.
         * Usefull for grid page switching support
         */
        lockUserActions: false,

        /**
         * Key codes
         */
        TAB_KEY_CODE: 9,
        ENTER_KEY_CODE: 13,
        ESCAPE_KEY_CODE: 27,
        ARROW_LEFT_KEY_CODE: 37,
        ARROW_TOP_KEY_CODE: 38,
        ARROW_RIGHT_KEY_CODE: 39,
        ARROW_BOTTOM_KEY_CODE: 40,

        constructor: function() {
            this.onKeyDown = _.bind(this.onKeyDown, this);
            this.hidePopover = _.bind(this.hidePopover, this);
            InlineEditingPlugin.__super__.constructor.apply(this, arguments);
        },

        enable: function() {
            this.listenTo(this.main, {
                afterMakeCell: this.onAfterMakeCell,
                shown: this.onGridShown,
                rowClicked: this.onGridRowClicked
            });
            this.listenTo(mediator, 'page:beforeChange', function() {
                if (this.editModeEnabled) {
                    this.exitEditMode(true);
                }
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
            if (this.editModeEnabled) {
                this.exitEditMode(true);
            }
            this.hidePopover();
            this.main.body.refresh();
            InlineEditingPlugin.__super__.disable.call(this);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.destroyPopover();
            return InlineEditingPlugin.__super__.dispose.call(this);
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
                originalRender.apply(this, arguments);
                if (_this.isEditable(cell)) {
                    this.$el.addClass('editable view-mode prevent-text-selection-on-dblclick');
                    this.$el.append('<i class="icon-edit hide-text">Edit</i>');
                }
                return this;
            };
            cell.events = _.extend({}, cell.events, {
                'dblclick': enterEditModeIfNeeded,
                'mouseleave': this.hidePopover,
                'mousedown .icon-edit': enterEditModeIfNeeded
            });
            delete cell.events.click;
            cell.delegateEvents();
        },

        onGridShown: function() {
            this.initPopover();
        },

        onGridRowClicked: function() {
            this.hidePopover();
        },

        initPopover: function() {
            this.main.$el.popover({
                content: __('oro.datagrid.inlineEditing.helpMessage'),
                container: document.body,
                selector: 'td.editable',
                placement: 'bottom',
                delay: {show: 1400, hide: 0},
                trigger: 'hover',
                animation: false
            });
        },

        hidePopover: function() {
            if (this.main.$el.data('popover')) {
                this.main.$el.popover('hide');
            }
        },

        destroyPopover: function() {
            if (this.main.$el.data('popover')) {
                this.main.$el.popover('destroy');
            }
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

        enterEditMode: function(cell, fromPreviousCell) {
            if (this.editModeEnabled) {
                this.exitEditMode(false);
            } else {
                if (backdropManager.isReleased(this.backdropId)) {
                    this.backdropId = backdropManager.hold();
                    $(document).on('keydown', this.onKeyDown);
                }
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

            var editorComponent = new CellEditorComponent(_.extend({}, editor.component_options, {
                cell: cell,
                view: CellEditorView,
                viewOptions: editor.viewOptions,
                fromPreviousCell: fromPreviousCell
            }));

            editorComponent.view.focus(!!fromPreviousCell);

            this.editorComponent = editorComponent;

            this.listenTo(editorComponent, 'saveAction', this.saveCurrentCell);
            this.listenTo(editorComponent, 'saveAndExitAction', this.saveCurrentCellAndExit);
            this.listenTo(editorComponent, 'cancelAction', this.exitEditMode, true);
            this.listenTo(editorComponent, 'saveAndEditNextAction', this.saveCurrentCellAndEditNext);
            this.listenTo(editorComponent, 'cancelAndEditNextAction', this.editNextCell);
            this.listenTo(editorComponent, 'saveAndEditPrevAction', this.saveCurrentCellAndEditPrev);
            this.listenTo(editorComponent, 'cancelAndEditPrevAction', this.editPrevCell);
            this.listenTo(editorComponent, 'saveAndEditNextRowAction', this.saveCurrentCellAndEditNextRow);
            this.listenTo(editorComponent, 'cancelAndEditNextRowAction', this.editNextRowCell);
            this.listenTo(editorComponent, 'saveAndEditPrevRowAction', this.saveCurrentCellAndEditPrevRow);
            this.listenTo(editorComponent, 'cancelAndEditPrevRowAction', this.editPrevRowCell);
        },

        toggleHeaderCellHighlight: function(cell, state) {
            var columnIndex = this.main.columns.indexOf(cell.column);
            var headerCell = this.main.findHeaderCellByIndex(columnIndex);
            if (headerCell) {
                headerCell.$el.toggleClass('header-cell-highlight', state);
            }
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
            if (!this.editorComponent.view.isChanged()) {
                return true;
            }
            if (!this.editorComponent.view.isValid()) {
                this.editorComponent.view.focus();
                return false;
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
            this.updateModel(cell.model, this.editorComponent, modelUpdateData);
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
            this.editor.save_api_accessor.send(cell.model.toJSON(), serverUpdateData, {}, {
                    processingMessage: __('oro.datagrid.inlineEditing.saving_progress'),
                    preventWindowUnload: __('oro.datagrid.inlineEditing.inline_edits')
                })
                .done(_.bind(InlineEditingPlugin.onSaveSuccess, ctx))
                .fail(_.bind(InlineEditingPlugin.onSaveError, ctx))
                .always(function() {
                    cell.$el.removeClass('loading');
                });
            if (exit !== false) {
                this.exitEditMode(cell);
            }
            return true;
        },

        updateModel: function(model, editorComponent, updateData) {
            // assume "undefined" as delete value request
            for (var key in updateData) {
                if (updateData.hasOwnProperty(key)) {
                    if (updateData[key] === editorComponent.view.UNSET_FIELD_VALUE) {
                        model.unset(key);
                        delete updateData[key];
                    }
                }
            }
            model.set(updateData);
        },

        exitEditMode: function(releaseBackdrop) {
            if (!this.editModeEnabled) {
                throw Error('Edit mode disabled');
            }
            this.editModeEnabled = false;
            if (this.currentCell.$el) {
                this.toggleHeaderCellHighlight(this.currentCell, false);
                this.currentCell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.currentCell.$el.addClass('view-mode');
                this.currentCell.$el.removeClass('edit-mode');
            }
            this.stopListening(this.editorComponent);
            this.editorComponent.dispose();
            if (releaseBackdrop !== false) {
                backdropManager.release(this.backdropId);
                $(document).off('keydown', this.onKeyDown);
            }
            delete this.editorComponent;
        },

        saveCurrentCellAndExit: function() {
            if (this.saveCurrentCell(false)) {
                this.exitEditMode(true);
            }
        },

        editNextCell: function() {
            this.editCellByIteratorMethod('next', false);
        },

        editNextRowCell: function() {
            this.editCellByIteratorMethod('nextRow', false);
        },

        editPrevCell: function() {
            this.editCellByIteratorMethod('prev', true);
        },

        editPrevRowCell: function() {
            this.editCellByIteratorMethod('prevRow', false);
        },

        editCellByIteratorMethod: function(iteratorMethod, fromPreviousCell) {
            var _this = this;
            this.lockUserActions = true;
            function checkEditable(cell) {
                if (!_this.isEditable(cell)) {
                    return _this.cellIterator[iteratorMethod]().then(checkEditable);
                }
                return cell;
            }
            this.exitEditMode(false);
            this.cellIterator[iteratorMethod]().then(checkEditable).done(function(cell) {
                _this.enterEditMode(cell, fromPreviousCell);
                _this.lockUserActions = false;
            }).fail(function() {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                _this.exitEditMode();
                _this.lockUserActions = false;
            });
        },

        saveCurrentCellAndEditNext: function() {
            this.saveCurrentCell(false);
            this.editNextCell();
        },

        saveCurrentCellAndEditPrev: function() {
            this.saveCurrentCell(false);
            this.editPrevCell();
        },

        saveCurrentCellAndEditNextRow: function() {
            this.saveCurrentCell(false);
            this.editNextRowCell();
        },

        saveCurrentCellAndEditPrevRow: function() {
            this.saveCurrentCell(false);
            this.editPrevRowCell();
        },

        _onRequireJsError: function() {
            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.inlineEditing.loadingError'));
        },

        /**
         * Keydown handler for the entire document
         *
         * @param {$.Event} e
         */
        onKeyDown: function(e) {
            if (this.editModeEnabled) {
                this.onGenericTabKeydown(e);
                this.onGenericEnterKeydown(e);
                this.onGenericEscapeKeydown(e);
                this.onGenericArrowKeydown(e);
            }
        },

        /**
         * Generic keydown handler, which handles ENTER
         *
         * @param {$.Event} e
         */
        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                if (!this.lockUserActions) {
                    if (this.saveCurrentCell(false)) {
                        if (e.ctrlKey) {
                            this.exitEditMode(true);
                        } else {
                            if (e.shiftKey) {
                                this.editPrevRowCell();
                            } else {
                                this.editNextRowCell();
                            }
                        }
                    }
                }
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles TAB
         *
         * @param {$.Event} e
         */
        onGenericTabKeydown: function(e) {
            if (e.keyCode === this.TAB_KEY_CODE) {
                if (!this.lockUserActions) {
                    if (this.saveCurrentCell(false)) {
                        if (e.shiftKey) {
                            this.editPrevCell();
                        } else {
                            this.editNextCell();
                        }
                    }
                }
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles ESCAPE
         *
         * @param {$.Event} e
         */
        onGenericEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                if (!this.lockUserActions) {
                    this.exitEditMode(true);
                }
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles ARROWS
         *
         * @param {$.Event} e
         */
        onGenericArrowKeydown: function(e) {
            if (e.altKey) {
                switch (e.keyCode) {
                    case this.ARROW_LEFT_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell(false)) {
                            this.editPrevCell();
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_RIGHT_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell(false)) {
                            this.editNextCell();
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_TOP_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell(false)) {
                            this.editPrevRowCell();
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_BOTTOM_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell(false)) {
                            this.editNextRowCell();
                        }
                        e.preventDefault();
                        break;
                }
            }
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

        onSaveError: function(jqXHR) {
            if (!this.cell.disposed && this.cell.$el) {
                var _this = this;
                this.cell.$el.addClass('save-fail');
                _.delay(function() {
                    _this.cell.$el.removeClass('save-fail');
                }, 2000);
            }
            this.cell.model.set(this.oldState);
            this.main.trigger('content:update');

            var errors = [];
            switch (jqXHR.responseJSON.code) {
                case 400:
                    var jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (var i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push.apply(errors, _.values(jqXHRerrors[i].errors));
                        }
                    }
                    break;
                case 403:
                    errors.push(__('You do not have permission to perform this action.'));
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });
        }
    });

    return InlineEditingPlugin;
});
