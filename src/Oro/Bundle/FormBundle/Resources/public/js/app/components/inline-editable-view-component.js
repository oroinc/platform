define(function(require) {
    'use strict';

    var InlineEditableViewComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseModel = require('oroui/js/app/models/base/model');
    var InlineEditorWrapperView = require('../views/inline-editable-wrapper-view');
    var frontendTypeMap = require('../../tools/frontend-type-map');
    var overlayTool = require('oroui/js/tools/overlay');
    var tools = require('oroui/js/tools');

    InlineEditableViewComponent = BaseComponent.extend({
        OVERLAY_TOOL_DEFAULTS: {
            position: {
                my: 'left top',
                at: 'left-1 top-4',
                collision: 'flipfit'
            },
            backdrop: true
        },

        METADATA_DEFAULTS: {
            inline_editing: {
                save_api_accessor: {
                    'class': 'oroui/js/tools/api-accessor'
                }
            }
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options.metadata = $.extend(true, {}, this.METADATA_DEFAULTS, options.metadata);
            this.fieldName = options.fieldName || 'value';
            // frontend type mapped to viewer/editor/reader
            var classes = frontendTypeMap[options.frontend_type];
            this.classes = classes;
            this.viewer = options.viewer || {};
            this.editor = options.editor || {};
            this.metadata = options.metadata;
            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);
            this.wrapper = new InlineEditorWrapperView({
                el: options._sourceElement,
                autoRender: true
            });
            this.view = new classes.viewer(_.extend({
                el: this.wrapper.getContainer(),
                autoRender: true,
                model: this.model,
                fieldName: 'value'
            }));
            if (this.classes.editor.processMetadata) {
                this.classes.editor.processMetadata(this.metadata);
            }
            this.wrapper.on('start-editing', this.enterEditMode, this);
            tools.loadModuleAndReplace(options.metadata.inline_editing.save_api_accessor, 'class').done(
                _.bind(function() {
                    var ConcreteApiAccessor = options.metadata.inline_editing.save_api_accessor['class'];
                    this.saveApiAccessor = new ConcreteApiAccessor(
                        _.omit(options.metadata.inline_editing.save_api_accessor, 'class'));
                }, this)
            );
        },

        enterEditMode: function() {
            var View = this.classes.editor;
            var viewInstance = new View(_.extend({}, this.editor.viewOptions, {
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName,
                metadata: this.metadata
            }));

            viewInstance.$el.addClass('inline-editor-wrapper');

            var overlayOptions = $.extend(true, {}, this.OVERLAY_TOOL_DEFAULTS, {
                position: {
                    of: this.wrapper.$el
                }
            });
            this.resizeTo(viewInstance, this.wrapper);
            this.overlay = overlayTool.createOverlay(viewInstance.$el, overlayOptions);

            this.listenTo(viewInstance, 'saveAction', this.saveCurrentCell);
            this.listenTo(viewInstance, 'saveAndExitAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAction', this.exitEditMode, true);
            this.listenTo(viewInstance, 'saveAndEditNextAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditNextAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditPrevAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditPrevAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditNextRowAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditNextRowAction', this.exitEditMode);
            this.listenTo(viewInstance, 'saveAndEditPrevRowAction', this.saveCurrentCellAndExit);
            this.listenTo(viewInstance, 'cancelAndEditPrevRowAction', this.exitEditMode);

            return viewInstance;
        },

        exitEditMode: function() {
            this.view.dispose();
            this.overlay.remove();
        },

        saveCurrentCellAndExit: function() {
            this.saveCurrentCell();
            this.exitEditMode();
        },

        saveCurrentCell: function() {

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
            var wrapper = this.wrapper;
            var serverUpdateData = this.editorComponent.view.getServerUpdateData();
            var modelUpdateData = this.editorComponent.view.getModelUpdateData();
            wrapper.$el.addClass('loading');
            var ctx = {
                wrapper: wrapper,
                oldState: _.pick(wrapper.model.toJSON(), _.keys(modelUpdateData))
            };
            this.updateModel(wrapper.model, this.editorComponent, modelUpdateData);
            if (this.metadata.editor.save_api_accessor.initialOptions.field_name) {
                var keys = _.keys(serverUpdateData);
                if (keys.length > 1) {
                    throw new Error('Only single field editors are supported with field_name option');
                }
                var newData = {};
                newData[this.editor.save_api_accessor.initialOptions.field_name] = serverUpdateData[keys[0]];
                serverUpdateData = newData;
            }
            this.metadata.editor.save_api_accessor.send(wrapper.model.toJSON(), serverUpdateData, {}, {
                    processingMessage: __('oro.form.inlineEditing.saving_progress'),
                    preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
                })
                .done(_.bind(InlineEditableViewComponent.onSaveSuccess, ctx))
                .fail(_.bind(InlineEditableViewComponent.onSaveError, ctx))
                .always(function() {
                    wrapper.$el.removeClass('loading');
                });
            return true;
        },

        /**
         * Resizes editor to cell width
         */
        resizeTo: function(view, cell) {
            view.$el.css({
                width: cell.$el.outerWidth()
            });
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
            mediator.execute('showFlashMessage', 'success', __('oro.form.inlineEditing.successMessage'));
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
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

    return InlineEditableViewComponent;
});
