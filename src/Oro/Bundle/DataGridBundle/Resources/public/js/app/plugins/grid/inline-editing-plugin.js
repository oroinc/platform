define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var CellIterator = require('../../../datagrid/cell-iterator');
    var gridViewsBuilder = require('../../../inline-editing/builder');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var Modal = require('oroui/js/modal');
    require('orodatagrid/js/app/components/cell-popup-editor-component');
    require('oroform/js/app/views/editor/text-editor-view');

    InlineEditingPlugin = BasePlugin.extend({
        /**
         * Help message for cells
         */
        helpMessage: __('oro.form.inlineEditing.helpMessage'),

        /**
         * Active editors set
         */
        activeEditorComponents: null,

        initialize: function(main, options) {
            this.activeEditorComponents = [];
            InlineEditingPlugin.__super__.initialize.apply(this, arguments);
        },

        enable: function() {
            this.main.$el.addClass('grid-editable');
            this.listenTo(this.main, {
                afterMakeCell: this.onAfterMakeCell
            });
            this.listenTo(this.main.collection, {
                beforeFetch: this.beforeGridCollectionFetch
            });
            if (this.main.columns) {
                this.listenColumnEvents();
            } else {
                this.listenToOnce(this.main, 'columns:ready', this.listenColumnEvents);
            }
            this.listenTo(mediator, {
                'page:beforeChange': this.removeActiveEditorComponents,
                'openLink:before': this.beforePageChange,
                'page:beforeRedirectTo': this.beforeRedirectTo
            });
            if (!this.options.metadata.inline_editing.save_api_accessor) {
                throw new Error('"save_api_accessor" option is required');
            }
            var ConcreteApiAccessor = this.options.metadata.inline_editing.save_api_accessor['class'];
            this.saveApiAccessor = new ConcreteApiAccessor(
                _.omit(this.options.metadata.inline_editing.save_api_accessor, 'class'));
            if (this.main.rendered) {
                this.main.body.refresh();
            }
            InlineEditingPlugin.__super__.enable.call(this);
            $(window).on('beforeunload.' + this.cid, _.bind(this.onWindowUnload, this));
        },

        listenColumnEvents: function() {
            this.listenTo(this.main.columns, {
                'change:renderable': this.onColumnStateChange
            });
        },

        disable: function() {
            $(window).off('.' + this.cid);
            this.removeActiveEditorComponents();
            if (!this.manager.disposing) {
                this.main.$el.removeClass('grid-editable');
                this.main.body.refresh();
            }
            InlineEditingPlugin.__super__.disable.call(this);
        },

        onColumnStateChange: function() {
            for (var i = 0; i < this.activeEditorComponents.length; i++) {
                var editorComponent = this.activeEditorComponents[i];
                if (!editorComponent.options.cell.column || !editorComponent.options.cell.column.get('renderable')) {
                    editorComponent.dispose();
                    i--;
                }
            }
        },

        confirmNavigation: function() {
            var confirmModal = new Modal({
                title: __('oro.datagrid.inline_editing.refresh_confirm_modal.title'),
                content: __('oro.ui.leave_page_with_unsaved_data_confirm'),
                okText: __('OK, got it.'),
                className: 'modal modal-primary',
                okButtonClass: 'btn-primary btn-large',
                cancelText: __('Cancel')
            });
            var deferredConfirmation = $.Deferred();

            deferredConfirmation.always(_.bind(function() {
                this.stopListening(confirmModal);
            }, this));

            this.listenTo(confirmModal, 'ok', function() {
                deferredConfirmation.resolve();
            });
            this.listenTo(confirmModal, 'cancel', function() {
                deferredConfirmation.reject(deferredConfirmation.promise(), 'abort');
            });

            confirmModal.open();

            return deferredConfirmation;
        },

        beforeGridCollectionFetch: function(collection, options) {
            if (this.hasChanges()) {
                var deferredConfirmation = this.confirmNavigation();
                deferredConfirmation.then(_.bind(this.removeActiveEditorComponents, this));
                options.waitForPromises.push(deferredConfirmation.promise());
            } else {
                this.removeActiveEditorComponents();
            }

        },

        beforeRedirectTo: function(queue) {
            if (this.hasChanges()) {
                var deferredConfirmation = this.confirmNavigation();
                queue.push(deferredConfirmation.promise());
            }
        },

        beforePageChange: function(e) {
            if (this.hasChanges()) {
                e.prevented = !window.confirm(__('oro.ui.leave_page_with_unsaved_data_confirm'));
            }
        },

        onWindowUnload: function() {
            if (this.hasChanges()) {
                return __('oro.ui.leave_page_with_unsaved_data_confirm');
            }
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

        hasChanges: function() {
            return _.some(this.activeEditorComponents, function(component) {
                return component.isChanged();
            });
        },

        isEditable: function(cell) {
            var columnMetadata = cell.column.get('metadata');
            if (!columnMetadata || !cell.column.get('renderable')) {
                return false;
            }
            var editable;
            var enableConfigValue = columnMetadata.inline_editing && columnMetadata.inline_editing.enable;
            // validateUrlParameters
            switch (this.options.metadata.inline_editing.behaviour) {
                case 'enable_all':
                    if (enableConfigValue !== false) {
                        editable = (columnMetadata.inline_editing && columnMetadata.inline_editing.enable === true) ||
                            (columnMetadata.type || gridViewsBuilder.DEFAULT_COLUMN_TYPE) in
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
            // apply entity restrictions to editable columns
            if (editable) {
                var modelRestrictions = cell.model.has('entity_restrictions') ?
                    cell.model.get('entity_restrictions') :
                    [];
                var fieldName = cell.column.get('name');
                var fieldRestrictions = _.filter(modelRestrictions, function (restriction) {
                    return restriction.field === fieldName;
                });
                if (!_.isEmpty(fieldRestrictions)) {
                    return false;
                }
            }

            return editable ?
                this.getCellEditorOptions(cell).save_api_accessor.validateUrlParameters(cell.model.toJSON()) :
                false;
        },

        getCellEditorOptions: function(cell) {
            var columnMetadata = cell.column.get('metadata');
            var editor = $.extend(true, {}, _.result(_.result(columnMetadata, 'inline_editing'), 'editor'));
            var saveApiAccessor = _.result(_.result(columnMetadata, 'inline_editing'), 'save_api_accessor');

            if (!editor.component_options) {
                editor.component_options = {};
            }

            if (saveApiAccessor) {
                if (!(saveApiAccessor instanceof ApiAccessor)) {
                    var saveApiOptions = _.extend({}, this.options.metadata.inline_editing.save_api_accessor,
                        saveApiAccessor);
                    var ConcreteApiAccessor = saveApiOptions.class;
                    saveApiAccessor = new ConcreteApiAccessor(_.omit(saveApiOptions, 'class'));
                }
                editor.save_api_accessor = saveApiAccessor;
            } else {
                // use main
                editor.save_api_accessor = this.saveApiAccessor;
            }

            var validationRules = _.result(columnMetadata.inline_editing, 'validation_rules') || {};

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
            var existingEditorComponent;
            // if there's previously focused editor, blur it
            if (this._focusedCell && this._focusedCell !== cell) {
                existingEditorComponent = this.getOpenedEditor(this._focusedCell);
                if (existingEditorComponent && existingEditorComponent.view) {
                    existingEditorComponent.view.blur();
                }
            }
            // focus to existing component
            existingEditorComponent = this.getOpenedEditor(cell);
            if (existingEditorComponent) {
                existingEditorComponent.enterEditMode();
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

            this.activeEditorComponents.push(editorComponent);
            this.listenTo(editorComponent, 'dispose', function() {
                if (this.disposed) {
                    // @TODO dix it. Rear case, for some reason inline inline-editing-plugin is already disposed
                    return;
                }
                var index = this.activeEditorComponents.indexOf(editorComponent);
                if (index !== -1) {
                    this.activeEditorComponents.splice(index, 1);
                }
            });

            this.listenTo(editorComponent.view, {
                focus: function() {
                    this._focusedCell = cell;
                    this.highlightCell(cell, true);
                },
                blur: function() {
                    this.highlightCell(cell, false);
                }
            });
            editorComponent.view.focus(!!fromPreviousCell);
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
            }).fail(function(obj, status) {
                if (status !== 'abort') {
                    mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
                }
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
            if (highlight === (this.lastHighlightedCell === cell)) {
                return;
            }
            if (this.lastHighlightedCell && highlight) {
                this.highlightCell(this.lastHighlightedCell, false);
            }
            this.toggleHeaderCellHighlight(cell, highlight);
            if (highlight) {
                if (!cell.disposed) {
                    cell.$el.parent('tr:first').addClass('row-edit-mode');
                }
                this.lastHighlightedCell = cell;
            } else {
                if (!cell.disposed) {
                    cell.$el.parent('tr:first').removeClass('row-edit-mode');
                }
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
