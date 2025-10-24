define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const CellIterator = require('../../../datagrid/cell-iterator');
    const ApiAccessor = require('oroui/js/tools/api-accessor');
    const Modal = require('oroui/js/modal');
    const SplitEventList = require('./inline-editing-plugin/split-event-list');
    const pageStateChecker = require('oronavigation/js/app/services/page-state-checker').default;
    require('orodatagrid/js/app/components/cell-popup-editor-component');
    require('oroform/js/app/views/editor/text-editor-view');

    const InlineEditingPlugin = BasePlugin.extend({
        /**
         * Help message for cells
         */
        helpMessage: __('oro.form.inlineEditing.helpMessage'),

        /**
         * Options for bootstrap modal
         */
        modalOptions: {
            title: __('oro.datagrid.inline_editing.refresh_confirm_modal.title'),
            content: __('oro.ui.leave_page_with_unsaved_data_confirm'),
            okText: __('Ok, got it'),
            className: 'modal modal-primary',
            cancelText: __('Cancel')
        },

        /**
         * Active editors set
         */
        activeEditorComponents: null,

        initialize: function(main, options) {
            this.activeEditorComponents = [];
            this.patchCellConstructor = this.patchCellConstructor.bind(this);
            this.hasChanges = this.hasChanges.bind(this);
            InlineEditingPlugin.__super__.initialize.call(this, main, options);
        },

        enable: function() {
            this.main.$el.addClass('grid-editable');

            this.listenTo(this.main.collection, {
                beforeFetch: this.beforeGridCollectionFetch
            });

            if (this.main.columns) {
                this.processColumnsAndListenEvents();
            } else {
                this.listenToOnce(this.main, 'columns:ready', this.processColumnsAndListenEvents);
            }
            this.listenTo(mediator, {
                'page:beforeChange': this.removeActiveEditorComponents,
                'openLink:before': this.beforePageChange,
                'page:beforeRedirectTo': this.beforeRedirectTo,
                'page:beforeRefresh': this.beforeRefresh
            });
            if (!this.options.metadata.inline_editing.save_api_accessor) {
                throw new Error('"save_api_accessor" option is required');
            }
            const ConcreteApiAccessor = this.options.metadata.inline_editing.save_api_accessor['class'];
            this.saveApiAccessor = new ConcreteApiAccessor(
                _.omit(this.options.metadata.inline_editing.save_api_accessor, 'class'));
            if (this.main.rendered) {
                this.main.body.refresh();
            }
            pageStateChecker.registerChecker(this.hasChanges);
            InlineEditingPlugin.__super__.enable.call(this);
            $(window).on('beforeunload.' + this.cid, this.onWindowUnload.bind(this));
        },

        processColumnsAndListenEvents: function() {
            this.processColumns();
            this.listenTo(this.main.columns, {
                'change:renderable': this.onColumnStateChange
            });
            this.main.columns.trigger('change:columnEventList');
        },

        processColumns: function() {
            this.main.columns.each(this.patchCellConstructor);
        },

        disable: function() {
            $(window).off('.' + this.cid);
            this.removeActiveEditorComponents();
            if (!this.manager.disposing) {
                this.main.columns.each(this.removePatchForCellConstructor);
                this.main.$el.removeClass('grid-editable');
                this.main.body.refresh();
            }
            pageStateChecker.removeChecker(this.hasChanges);
            InlineEditingPlugin.__super__.disable.call(this);
        },

        onColumnStateChange: function() {
            for (let i = 0; i < this.activeEditorComponents.length; i++) {
                const editorComponent = this.activeEditorComponents[i];
                if (!editorComponent.options.cell.column || !editorComponent.options.cell.column.get('renderable')) {
                    editorComponent.dispose();
                    i--;
                }
            }
        },

        confirmNavigation: function() {
            const confirmModal = new Modal(this.modalOptions);
            const deferredConfirmation = $.Deferred();

            deferredConfirmation.always(() => {
                this.stopListening(confirmModal);
            });

            this.listenTo(confirmModal, 'ok', function() {
                deferredConfirmation.resolve();
            });
            this.listenTo(confirmModal, 'cancel close', function() {
                deferredConfirmation.reject(deferredConfirmation.promise(), 'abort');
            });
            // once navigation is confirmed, set changes to be ignored
            deferredConfirmation.then(() => pageStateChecker.ignoreChanges());
            confirmModal.open();

            return deferredConfirmation;
        },

        beforeGridCollectionFetch: function(collection, options) {
            if (this.hasChanges()) {
                const deferredConfirmation = this.confirmNavigation();
                deferredConfirmation.then(this.removeActiveEditorComponents.bind(this));
                options.waitForPromises.push(deferredConfirmation.promise());
            } else {
                this.removeActiveEditorComponents();
            }
        },

        beforeRefresh(queue) {
            return this.beforeNavigation(queue);
        },

        beforeRedirectTo(queue) {
            return this.beforeNavigation(queue);
        },

        beforeNavigation: function(queue) {
            if (this.hasChanges()) {
                const deferredConfirmation = this.confirmNavigation();
                queue.push(deferredConfirmation.promise());
            }
        },

        beforePageChange: function(e) {
            if (!e.prevented && this.hasChanges()) {
                e.prevented = !window.confirm(__('oro.ui.leave_page_with_unsaved_data_confirm'));
            }
        },

        onWindowUnload: function() {
            if (this.hasChanges() && !pageStateChecker.hasChangesIgnored()) {
                return __('oro.ui.leave_page_with_unsaved_data_confirm');
            }
        },

        /**
         * Extend passed cell constructor via some methods and properties
         *
         * @param {Constructor} Cell
         * @returns {Constructor}
         */
        cellPatcher(Cell) {
            const inlineEditingPlugin = this;
            const oldClassName = Cell.prototype.className;
            const splitEventsList = new SplitEventList(Cell, 'isEditable', {
                'dblclick': 'enterEditModeIfNeeded',
                'mousedown [data-role=edit]': 'enterEditModeIfNeeded',
                'click': _.noop,
                'mouseenter': 'delayedIconRender'
            });

            return Cell.extend({
                constructor: function(options) {
                    // column should be initialized to valid work of className generation
                    this.column = options.column;
                    Cell.call(this, options);
                },
                className: _.isFunction(oldClassName)
                    ? function() {
                        const calculatedClassName = oldClassName.call(this);
                        const addClassName = inlineEditingPlugin.isEditable(this)
                            ? 'editable view-mode prevent-text-selection-on-dblclick' : '';
                        return (calculatedClassName ? calculatedClassName + ' ' : '') + addClassName;
                    }
                    : function() {
                        const addClassName = inlineEditingPlugin.isEditable(this)
                            ? 'editable view-mode prevent-text-selection-on-dblclick' : '';
                        return (oldClassName ? oldClassName + ' ' : '') + addClassName;
                    },
                events: splitEventsList.generateDeclaration(),

                delayedIconRender: function() {
                    if (!this.$('[data-role="edit"]').length) {
                        this.$el.append(`<span class="inline-editor-edit-action">
                            <button data-role="edit"
                                    class="inline-actions-btn skip-row-click hide-text"
                                    title="${_.escape(__('Edit'))}">
                                <span class="fa-pencil" aria-hidden="true"></span>
                            </button>
                       </span>`);
                        this.$el.attr('title', inlineEditingPlugin.helpMessage);
                    }
                },

                isEditable: function() {
                    return inlineEditingPlugin.isEditable(this);
                },

                enterEditModeIfNeeded: function(e) {
                    if (this.isEditable()) {
                        inlineEditingPlugin.enterEditMode(this);
                    }
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        },

        patchCellConstructor: function(column) {
            const Cell = column.get('cell');

            column.set({
                cellPatcher: this.cellPatcher.bind(this),
                cell: this.cellPatcher(Cell),
                oldCell: Cell
            });
        },

        removePatchForCellConstructor: function(column) {
            if (column.get('oldCell')) {
                column.set({
                    cell: column.get('oldCell'),
                    oldCell: false
                });
            }
        },

        hasChanges: function() {
            return _.some(this.activeEditorComponents, function(component) {
                return component.isChanged();
            });
        },

        isEditable: function(cell) {
            const columnMetadata = cell.column.get('metadata');
            if (!columnMetadata || !cell.model || !cell.column.get('renderable')) {
                return false;
            }
            const fieldName = cell.column.get('name');
            const fullRestriction = _.find(cell.model.get('entity_restrictions'), function(restriction) {
                return restriction.field === fieldName && restriction.mode === 'full';
            });
            if (fullRestriction) {
                return false;
            }
            return columnMetadata.inline_editing && columnMetadata.inline_editing.enable
                ? this.getCellEditorOptions(cell)
                    .save_api_accessor.validateUrlParameters(cell.model.toJSON())
                : false;
        },

        getCellEditorOptions: function(cell) {
            let cellEditorOptions = cell.column.get('cellEditorOptions');
            if (!cellEditorOptions) {
                const columnMetadata = cell.column.get('metadata');
                cellEditorOptions = $.extend(true, {}, _.result(_.result(columnMetadata, 'inline_editing'), 'editor'));
                let saveApiAccessor = _.result(_.result(columnMetadata, 'inline_editing'), 'save_api_accessor');

                if (!cellEditorOptions.component_options) {
                    cellEditorOptions.component_options = {};
                }

                if (saveApiAccessor) {
                    if (!(saveApiAccessor instanceof ApiAccessor)) {
                        const saveApiOptions = _.extend({}, this.options.metadata.inline_editing.save_api_accessor,
                            saveApiAccessor);
                        const ConcreteApiAccessor = saveApiOptions.class;
                        saveApiAccessor = new ConcreteApiAccessor(_.omit(saveApiOptions, 'class'));
                    }
                    cellEditorOptions.save_api_accessor = saveApiAccessor;
                } else {
                    // use main
                    cellEditorOptions.save_api_accessor = this.saveApiAccessor;
                }

                const validationRules = _.result(columnMetadata.inline_editing, 'validation_rules') || {};

                _.each(validationRules, function(params, ruleName) {
                    // normalize rule's params, in case is it was defined as 'NotBlank: ~'
                    validationRules[ruleName] = params || {};
                });

                cellEditorOptions.viewOptions = $.extend(true, {}, cellEditorOptions.view_options || {}, {
                    validationRules: validationRules
                });

                cell.column.set('cellEditorOptions', cellEditorOptions);
            }
            return cellEditorOptions;
        },

        getOpenedEditor: function(cell) {
            return _.find(this.activeEditorComponents, function(editor) {
                return editor.options.cell === cell;
            });
        },

        enterEditMode: function(cell, fromPreviousCell) {
            cell.trigger('before-enter-edit-mode');

            let existingEditorComponent;
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

            const editor = this.getCellEditorOptions(cell);
            editor.viewOptions.className = this.buildClassNames(editor, cell).join(' ');

            const CellEditorComponent = editor.component;
            const CellEditorView = editor.view;

            if (!CellEditorView) {
                throw new Error('Editor view in not available for `' + cell.column.get('name') + '` column');
            }

            const editorComponent = new CellEditorComponent(_.extend({}, editor.component_options, {
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
                if (this._focusedCell) {
                    this.highlightCell(this._focusedCell, false);
                }
                const index = this.activeEditorComponents.indexOf(editorComponent);
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
            editorComponent.view.scrollIntoView();
            editorComponent.view.focus(!!fromPreviousCell);
        },

        buildClassNames: function(editor, cell) {
            const classNames = ['skip-row-click'];
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
            const fromPreviousCell = iteratorMethod === 'prev';
            this.trigger('lockUserActions', true);
            const cellIterator = new CellIterator(this.main, cell);
            const checkEditable = cell => {
                if (!this.isEditable(cell)) {
                    return cellIterator[iteratorMethod]().then(checkEditable);
                }
                return cell;
            };
            cellIterator[iteratorMethod]().then(checkEditable).done(cell => {
                this.enterEditMode(cell, fromPreviousCell);
                this.trigger('lockUserActions', false);
            }).fail((obj, status) => {
                this.trigger('lockUserActions', false);
            });
        },

        removeActiveEditorComponents: function() {
            for (let i = 0; i < this.activeEditorComponents.length; i++) {
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
                    cell.$el.parent('tr').first().addClass('row-edit-mode');
                }
                this.lastHighlightedCell = cell;
            } else {
                if (!cell.disposed) {
                    cell.$el.parent('tr').first().removeClass('row-edit-mode');
                }
                this.lastHighlightedCell = null;
            }
        },

        toggleHeaderCellHighlight: function(cell, state) {
            const columnIndex = this.main.columns.indexOf(cell.column);
            const headerCell = this.main.findHeaderCellByIndex(columnIndex);
            if (headerCell) {
                headerCell.$el.toggleClass('header-cell-highlight', state);
            }
        }
    });

    return InlineEditingPlugin;
});
