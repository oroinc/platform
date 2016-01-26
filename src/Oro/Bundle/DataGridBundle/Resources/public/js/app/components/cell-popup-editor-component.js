define(function(require) {
    'use strict';

    var CellPopupEditorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var overlayTool = require('oroui/js/tools/overlay');

    CellPopupEditorComponent = BaseComponent.extend({
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

        /**
         * If true interface should not respond to user actions.
         * Useful for grid page switching support
         */
        lockUserActions: false,

        OVERLAY_TOOL_DEFAULTS: {
            position: {
                my: 'left top',
                at: 'left top',
                collision: 'flip',
                using: function(position, information) {
                    information.element.element.css({
                        left: position.left >= 0 ? position.left - 4 : position.left + 4,
                        top: position.top >= -1 ? position.top - 4 : position.top
                    });
                }
            }
        },

        listen: {
            saveAction: 'saveCurrentCell',
            cancelAction: 'cancelEditing',
            saveAndExitAction: 'saveCurrentCell',
            saveAndEditNextAction: 'saveCurrentCellAndEditNext',
            cancelAndEditNextAction: 'editNextCell',
            saveAndEditPrevAction: 'saveCurrentCellAndEditPrev',
            cancelAndEditPrevAction: 'editPrevCell',
            saveAndEditNextRowAction: 'saveCurrentCellAndEditNextRow',
            cancelAndEditNextRowAction: 'editNextRowCell',
            saveAndEditPrevRowAction: 'saveCurrentCellAndEditPrevRow',
            cancelAndEditPrevRowAction: 'editPrevRowCell',
            'inlineEditor:focus mediator': 'onInlineEditorFocus'
        },

        initialize: function(options) {
            this.options = options || {};
            if (!this.options.plugin) {
                throw new Error('Option "plugin" is required');
            }
            if (!this.options.cell) {
                throw new Error('Option "cell" is required');
            }
            if (!this.options.view) {
                throw new Error('Option "view" is required');
            }
            if (!this.options.save_api_accessor) {
                throw new Error('Option "save_api_accessor" is required');
            }
            this.listenTo(this.options.plugin, 'lockUserActions', function(value) {
                this.lockUserActions = value;
            });
            CellPopupEditorComponent.__super__.initialize.apply(this, arguments);
            this.enterEditMode();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.options && this.options.cell && this.options.cell.$el) {
                this.options.cell.$el.removeClass('has-error');
            }
            CellPopupEditorComponent.__super__.dispose.apply(this, arguments);
        },

        createView: function() {
            var View = this.options.view;
            var cell = this.options.cell;
            var viewOptions = _.extend({}, this.options.viewOptions, {
                autoRender: true,
                model: cell.model,
                fieldName: cell.column.get('name'),
                metadata: cell.column.get('metadata')
            });
            if (this.newState && viewOptions.fieldName in this.newState) {
                this.updateModel(cell.model, this.oldState);
                this.options.plugin.main.trigger('content:update');
                viewOptions.value = this.newState[viewOptions.fieldName];
            }
            var viewInstance = this.view = new View(viewOptions);

            viewInstance.$el.addClass('inline-editor-wrapper');

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                insertInto: cell.$el,
                position: {
                    of: cell.$el,
                    within: cell.$el.closest('tbody')
                }
            });
            this.resizeToCell(viewInstance, cell);
            var overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            viewInstance.trigger('change:visibility');

            this.listenTo(viewInstance, {
                dispose: function() {
                    overlay.remove();
                },
                change: function() {
                    viewInstance.$el.toggleClass('show-overlay', !viewInstance.isValid());
                    cell.$el.toggleClass('has-error', !viewInstance.isValid());
                },
                keydown: this.onKeyDown,
                focus: function() {
                    mediator.trigger('inlineEditor:focus', viewInstance);
                    overlay.focus();
                },
                blur: function() {
                    if (viewInstance.isValid() && viewInstance.isChanged()) {
                        this.saveCurrentCell();
                    }
                    overlay.blur();
                }
            });
            viewInstance.trigger('change');

            if (this.backendErrors) {
                this.view.$el.toggleClass('show-overlay', true);
                this.view.showBackendErrors(this.backendErrors);
            }
        },

        onInlineEditorFocus: function(view) {
            if (!this.view || view === this.view) {
                return;
            }
            if (!this.view.isChanged()) {
                this.exitEditMode(true);
            } else {
                this.options.cell.$el.toggleClass('has-error', !this.view.isValid());
                this.newState = this.view.getModelUpdateData();
                this.oldState = _.pick(this.options.cell.model.toJSON(), _.keys(this.newState));
                this.exitEditMode(); // have to exit first, before model is updated, to dispose view properly
                this.updateModel(this.options.cell.model, this.newState);
                this.options.plugin.main.trigger('content:update');
            }
        },

        /**
         * Resizes editor to cell width
         */
        resizeToCell: function(view, cell) {
            view.$el.width(cell.$el.outerWidth() + this.getWidthIncrement());
        },

        /**
         * Returns cell editor width increment
         *
         * @returns {number}
         */
        getWidthIncrement: function() {
            return 67;
        },

        /**
         * Saves current cell and returns flag if was saved successfully or promise object
         *
         * @return {boolean|Promise}
         */
        saveCurrentCell: function() {
            if (!this.view.isChanged()) {
                this.exitEditMode(true);
                return true;
            }
            if (!this.view.isValid()) {
                return false;
            }

            var cell = this.options.cell;
            var serverUpdateData = this.view.getServerUpdateData();
            var modelUpdateData = this.newState = this.view.getModelUpdateData();
            cell.$el.addClass('loading');
            this.oldState = _.pick(cell.model.toJSON(), _.keys(modelUpdateData));
            this.exitEditMode(); // have to exit first, before model is updated, to dispose view properly

            this.updateModel(cell.model, modelUpdateData);
            this.options.plugin.main.trigger('content:update');
            if (this.options.save_api_accessor.initialOptions.field_name) {
                var keys = _.keys(serverUpdateData);
                if (keys.length > 1) {
                    throw new Error('Only single field editors are supported with field_name option');
                }
                var newData = {};
                newData[this.options.save_api_accessor.initialOptions.field_name] = serverUpdateData[keys[0]];
                serverUpdateData = newData;
            }
            var savePromise = this.options.save_api_accessor.send(cell.model.toJSON(), serverUpdateData, {}, {
                processingMessage: __('oro.form.inlineEditing.saving_progress'),
                preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
            });
            if (this.constructor.processSavePromise) {
                savePromise = this.constructor.processSavePromise(savePromise, cell.column.get('metadata'));
            }
            if (this.options.view.processSavePromise) {
                savePromise = this.options.view.processSavePromise(savePromise, cell.column.get('metadata'));
            }
            savePromise.done(_.bind(this.onSaveSuccess, this))
                .fail(_.bind(this.onSaveError, this))
                .always(function() {
                    cell.$el.removeClass('loading');
                });
            return savePromise;
        },

        updateModel: function(model, updateData) {
            // assume "undefined" as delete value request
            for (var key in updateData) {
                if (updateData.hasOwnProperty(key)) {
                    if (updateData[key] === void 0) {
                        model.unset(key);
                        delete updateData[key];
                    }
                }
            }
            model.set(updateData);
        },

        /**
         * Shows editor view (create first if it did not exist)
         */
        enterEditMode: function() {
            if (!this.view) {
                this.options.cell.$el.removeClass('view-mode save-fail');
                this.options.cell.$el.addClass('edit-mode');
                this.createView(this.options);
                // rethrow view events on component
                this.listenTo(this.view, 'all', function(eventName) {
                    if (eventName !== 'dispose') {
                        this.trigger.apply(this, arguments);
                    }
                }, this);
            }
        },

        /**
         * Hides editor view and removes listeners
         *
         * @param {boolean=} withDispose if passed true disposes the component
         */
        exitEditMode: function(withDispose) {
            if (this.view) {
                this.options.cell.$el.removeClass('edit-mode');
                this.options.cell.$el.addClass('view-mode');
                this.view.dispose();
                this.stopListening(this.view);
                delete this.view;
            }

            if (withDispose) {
                this.dispose();
            }
        },

        toggleHeaderCellHighlight: function(cell, state) {
            var columnIndex = this.options.plugin.main.columns.indexOf(cell.column);
            var headerCell = this.options.plugin.main.findHeaderCellByIndex(columnIndex);
            if (headerCell) {
                headerCell.$el.toggleClass('header-cell-highlight', state);
            }
        },

        revertChanges: function() {
            if (!this.options.cell.disposed && this.oldState) {
                this.options.cell.model.set(this.oldState);
                delete this.oldState;
                this.options.plugin.main.trigger('content:update');
            }
        },

        cancelEditing: function() {
            this.revertChanges();
            this.exitEditMode(true);
        },

        editNextCell: function() {
            this.exitAndNavigate('editNextCell');
        },

        editNextRowCell: function() {
            this.exitAndNavigate('editNextRowCell');
        },

        editPrevCell: function() {
            this.exitAndNavigate('editPrevCell');
        },

        editPrevRowCell: function() {
            this.exitAndNavigate('editPrevRowCell');
        },

        exitAndNavigate: function(method) {
            var plugin = this.options.plugin;
            var cell = this.options.cell;
            this.exitEditMode(true);
            plugin[method](cell);
        },

        saveCurrentCellAndEditNext: function() {
            this.saveAndNavigate('editNextCell');
        },

        saveCurrentCellAndEditPrev: function() {
            this.saveAndNavigate('editPrevCell');
        },

        saveCurrentCellAndEditNextRow: function() {
            this.saveAndNavigate('editNextRowCell');
        },

        saveCurrentCellAndEditPrevRow: function() {
            this.saveAndNavigate('editPrevRowCell');
        },

        saveAndNavigate: function(method) {
            var plugin = this.options.plugin;
            var cell = this.options.cell;
            this.saveCurrentCell();
            plugin[method](cell);
        },

        /**
         * Keydown handler for the editor view
         *
         * @param {$.Event} e
         */
        onKeyDown: function(e) {
            this.onGenericTabKeydown(e);
            this.onGenericEnterKeydown(e);
            this.onGenericEscapeKeydown(e);
            this.onGenericArrowKeydown(e);
        },

        /**
         * Generic keydown handler, which handles ENTER
         *
         * @param {$.Event} e
         */
        onGenericEnterKeydown: function(e) {
            if (this.disposed) {
                return;
            }
            var plugin = this.options.plugin;
            var cell = this.options.cell;
            if (e.keyCode === this.ENTER_KEY_CODE) {
                if (!this.lockUserActions) {
                    if (this.saveCurrentCell()) {
                        if (!e.ctrlKey) {
                            if (e.shiftKey) {
                                plugin.editPrevRowCell(cell);
                            } else {
                                plugin.editNextRowCell(cell);
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
            if (this.disposed) {
                return;
            }
            var plugin = this.options.plugin;
            var cell = this.options.cell;
            if (e.keyCode === this.TAB_KEY_CODE) {
                if (!this.lockUserActions) {
                    if (this.saveCurrentCell()) {
                        if (e.shiftKey) {
                            plugin.editPrevCell(cell);
                        } else {
                            plugin.editNextCell(cell);
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
                    this.revertChanges();
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
            if (this.disposed) {
                return;
            }
            var plugin = this.options.plugin;
            var cell = this.options.cell;
            if (e.altKey) {
                switch (e.keyCode) {
                    case this.ARROW_LEFT_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell()) {
                            plugin.editPrevCell(cell);
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_RIGHT_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell()) {
                            plugin.editNextCell(cell);
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_TOP_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell()) {
                            plugin.editPrevRowCell(cell);
                        }
                        e.preventDefault();
                        break;
                    case this.ARROW_BOTTOM_KEY_CODE:
                        if (!this.lockUserActions && this.saveCurrentCell()) {
                            plugin.editNextRowCell(cell);
                        }
                        e.preventDefault();
                        break;
                }
            }
        },

        onSaveSuccess: function(response) {
            if (!this.options.cell.disposed && this.options.cell.$el) {
                if (response) {
                    var routeParametersRenameMap = _.invert(this.options.cell.column.get('metadata').inline_editing.
                        save_api_accessor.routeParametersRenameMap);
                    _.each(response, function(item, i) {
                        var propName = routeParametersRenameMap.hasOwnProperty(i) ? routeParametersRenameMap[i] : i;
                        if (this.options.cell.model.has(propName)) {
                            this.options.cell.model.set(propName, item);
                        }
                    }, this);
                }
                this.options.cell.$el
                    .removeClass('save-fail has-error')
                    .addClassTemporarily('save-success', 2000);
            }
            mediator.execute('showFlashMessage', 'success', __('oro.form.inlineEditing.successMessage'));
            delete this.oldState;
            delete this.newState;
            delete this.backendErrors;
            this.exitEditMode(true);
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            var errors = [];
            var fieldLabel;

            if (errorCode === 400) {
                this.onValidationError(jqXHR);
                return;
            }

            if (!this.options.cell.disposed) {
                fieldLabel = this.options.cell.column.get('label');
                this.options.cell.$el.addClass('save-fail');
            }

            switch (errorCode) {
                case 403:
                    errors.push(__('oro.datagrid.inline_editing.message.save_field.permission_denied',
                        {fieldLabel: fieldLabel}));
                    break;
                case 500:
                    if (jqXHR.responseJSON.message) {
                        errors.push(__(jqXHR.responseJSON.message));
                    } else {
                        errors.push(__('oro.ui.unexpected_error'));
                    }
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            this.revertChanges();
            this.exitEditMode(true);

            _.each(errors, function(value) {
                mediator.execute('showMessage', 'error', value);
            });
        },

        onValidationError: function(jqXHR) {
            var message;
            var fieldName;
            var fieldLabel;

            if (!this.options.cell.disposed) {
                fieldName = this.options.cell.column.get('name');
                fieldLabel = this.options.cell.column.get('label');
                this.options.cell.$el.addClass('has-error');
            }
            if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                var backendErrors = {};
                _.each(jqXHR.responseJSON.errors.children, function(item, name) {
                    if (fieldName === name && _.isArray(item.errors)) {
                        backendErrors.value = item.errors[0];
                    }
                }, this);
                this.backendErrors = backendErrors;
                message = __('oro.datagrid.inline_editing.message.save_field.validation_error',
                    {fieldLabel: fieldLabel, error: backendErrors.value});
                mediator.execute('showFlashMessage', 'error', message);
            }
        }
    });

    return CellPopupEditorComponent;
});
