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
                at: 'left-4 top-4',
                collision: 'flipfit'
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

        createView: function() {
            var View = this.options.view;
            var viewInstance = this.view = new View(_.extend({}, this.options.viewOptions, {
                autoRender: true,
                model: this.options.cell.model,
                fieldName: this.options.cell.column.get('name'),
                metadata: this.options.cell.column.get('metadata')
            }));

            viewInstance.$el.addClass('inline-editor-wrapper');

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                insertInto: this.options.cell.$el,
                position: {
                    of: this.options.cell.$el
                }
            });
            this.resizeToCell(viewInstance, this.options.cell);
            var overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);
            viewInstance.trigger('change:visibility');

            this.listenTo(viewInstance, 'dispose', function() {
                overlay.remove();
            });
            viewInstance.on('change', function() {
                viewInstance.$el.toggleClass('show-overlay', !viewInstance.isValid());
            });

            viewInstance.on('keydown', this.onKeyDown, this);
            viewInstance.on('focus', function() {
                mediator.trigger('inlineEditor:focus', viewInstance);
            });
            viewInstance.on('blur', function() {
                if (viewInstance.isChanged()) {
                    this.saveCurrentCellAndExit();
                }
            }, this);
        },

        onInlineEditorFocus: function(view) {
            if (!this.view || view === this.view) {
                return;
            }
            if (!this.view.isChanged()) {
                this.exitEditMode();
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
            var modelUpdateData = this.view.getModelUpdateData();
            cell.$el.addClass('loading');
            this.oldState = _.pick(cell.model.toJSON(), _.keys(modelUpdateData));
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

            this.exitEditMode();
            return savePromise;
        },

        updateModel: function(model, updateData) {
            // assume "undefined" as delete value request
            for (var key in updateData) {
                if (updateData.hasOwnProperty(key)) {
                    if (updateData[key] === this.view.UNSET_FIELD_VALUE) {
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
                this.createView(this.options);
                // rethrow view events on component
                this.listenTo(this.view, 'all', function() {
                    this.trigger.apply(this, arguments);
                }, this);
            }

            if (this.options.cell.$el) {
                this.toggleHeaderCellHighlight(this.options.cell, true);
                this.options.cell.$el.parent('tr:first').addClass('row-edit-mode');
                this.options.cell.$el.removeClass('view-mode');
                this.options.cell.$el.addClass('edit-mode');
            }
        },

        /**
         * Hides editor view and removes listeners
         *
         * @param {boolean=} withDispose if passed true disposes the component
         */
        exitEditMode: function(withDispose) {
            if (this.view) {
                this.view.dispose();
                this.stopListening(this.view);
                delete this.view;
            }

            if (this.options.cell.$el) {
                this.toggleHeaderCellHighlight(this.options.cell, false);
                this.options.cell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.options.cell.$el.addClass('view-mode');
                this.options.cell.$el.removeClass('edit-mode');
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
                this.options.cell.$el.addClassTemporarily('save-success', 2000);
            }
            mediator.execute('showFlashMessage', 'success', __('oro.form.inlineEditing.successMessage'));
            delete this.oldState;
            this.exitEditMode(true);
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            var errors = [];
            switch (errorCode) {
                case 400:
                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                        this.enterEditMode();
                        this.view.$el.toggleClass('show-overlay', true);
                        this.view.showBackendErrors(jqXHR.responseJSON.errors);
                        return;
                    }
                    break;
                case 403:
                    errors.push(__('oro.datagrid.inline_editing.message.save_field.permission_denied',
                        {fieldName: this.cell.column.get('label')}));
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

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });

            if (!this.options.cell.disposed && this.options.cell.$el) {
                this.options.cell.$el.addClassTemporarily('save-fail', 2000);
            }
            this.revertChanges();
            this.exitEditMode(true);
        }

    });

    return CellPopupEditorComponent;
});
