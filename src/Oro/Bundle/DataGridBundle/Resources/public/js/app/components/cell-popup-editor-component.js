define(function(require) {
    'use strict';

    var CellPopupEditorComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var CellIterator = require('../../datagrid/cell-iterator');
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

        OVERLAY_TOOL_DEFAULTS: {
            position: {
                my: 'left top',
                at: 'left-4 top-4',
                collision: 'flipfit'
            }
        },

        initialize: function(options) {
            this.options = options || {};

            this.view = this.createView(options);

            // rethrow view events on component
            this.listenTo(this.view, 'all', function() {
                this.trigger.apply(this, arguments);
            }, this);

            CellPopupEditorComponent.__super__.initialize.apply(this, arguments);
        },

        createView: function() {
            var View = this.options.view;
            var viewInstance = new View(_.extend({}, this.options.viewOptions, {
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

            viewInstance.on('dispose', function() {
                overlay.remove();
            });

            viewInstance.on('change', function() {
                viewInstance.$el.toggleClass('show-overlay', !viewInstance.isValid());
            });

            viewInstance.on('keydown', this.onKeyDown, this);

            this.on('saveAction', this.saveCurrentCell);
            this.on('saveAndExitAction', this.saveCurrentCellAndExit);
            this.on('cancelAction', _.bind(this.exitEditMode, this, true));
            this.on('saveAndEditNextAction', this.saveCurrentCellAndEditNext);
            this.on('cancelAndEditNextAction', this.editNextCell);
            this.on('saveAndEditPrevAction', this.saveCurrentCellAndEditPrev);
            this.on('cancelAndEditPrevAction', this.editPrevCell);
            this.on('saveAndEditNextRowAction', this.saveCurrentCellAndEditNextRow);
            this.on('cancelAndEditNextRowAction', this.editNextRowCell);
            this.on('saveAndEditPrevRowAction', this.saveCurrentCellAndEditPrevRow);
            this.on('cancelAndEditPrevRowAction', this.editPrevRowCell);

            this.toggleHeaderCellHighlight(this.options.cell, true);

            return viewInstance;
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

        saveCurrentCell: function(exit) {
            if (!this.view.isChanged()) {
                return true;
            }
            if (!this.view.isValid()) {
                this.view.focus();
                return false;
            }
            var cell = this.options.cell;
            var serverUpdateData = this.view.getServerUpdateData();
            var modelUpdateData = this.view.getModelUpdateData();
            cell.$el.addClass('loading');
            this.oldState = _.pick(cell.model.toJSON(), _.keys(modelUpdateData));
            this.updateModel(cell.model, modelUpdateData);
            this.options.grid.trigger('content:update');
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
            // TODO: specify exit conditions
            if (exit !== false) {
                this.exitEditMode(cell);
            }
            return true;
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

        exitEditMode: function() {
            if (this.options.cell.$el) {
                this.toggleHeaderCellHighlight(this.options.cell, false);
                this.options.cell.$el.parent('tr:first').removeClass('row-edit-mode');
                this.options.cell.$el.addClass('view-mode');
                this.options.cell.$el.removeClass('edit-mode');
            }
            this.stopListening(this);
            this.dispose();
        },

        toggleHeaderCellHighlight: function(cell, state) {
            var columnIndex = this.options.grid.columns.indexOf(cell.column);
            var headerCell = this.options.grid.findHeaderCellByIndex(columnIndex);
            if (headerCell) {
                headerCell.$el.toggleClass('header-cell-highlight', state);
            }
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

        createCellIterator: function() {
            return new CellIterator(this.options.grid, this.options.cell);
        },

        editCellByIteratorMethod: function(iteratorMethod, fromPreviousCell) {
            var _this = this;
            var cellIterator = this.createCellIterator();
            this.lockUserActions = true;
            function checkEditable(cell) {
                if (!_this.options.plugin.isEditable(cell)) {
                    return cellIterator[iteratorMethod]().then(checkEditable);
                }
                return cell;
            }
            cellIterator[iteratorMethod]().then(checkEditable).done(function(cell) {
                _this.options.plugin.enterEditMode(cell, fromPreviousCell);
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
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            if (!this.options.cell.disposed && this.options.cell.$el) {
                this.options.cell.$el.addClassTemporarily('save-fail', 2000);
            }
            this.options.cell.model.set(this.oldState);
            this.options.grid.trigger('content:update');

            var errors = [];
            switch (errorCode) {
                case 400:
                    var jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (var i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push.apply(errors, _.values(jqXHRerrors[i].errors));
                        }
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
        }

    });

    return CellPopupEditorComponent;
});
