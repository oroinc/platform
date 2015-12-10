/** @lends InlineEditableViewComponent */
define(function(require) {
    'use strict';

    /**
     * Allows to connect inline editors on view pages.
     * Currently used only for tags-editor. See [index of supported editors](../editor)
     * @TODO update after connecting other editors
     *
     * Sample:
     *
     * ```twig
     * {% import 'OroUIBundle::macros.html.twig' as UI %}
     * <div class="tags-view-form-container" {{ UI.renderPageComponentAttributes({
     *    module: 'oroform/js/app/components/inline-editable-view-component',
     *    options: {
     *        frontend_type: 'tags',
     *        value: oro_tag_get_list(entity),
     *        fieldName: 'tags',
     *        metadata: {
     *            inline_editing: {
     *                enable: resource_granted('oro_tag_assign_unassign'),
     *                save_api_accessor: {
     *                    route: 'oro_api_post_taggable',
     *                    http_method: 'POST',
     *                    default_route_parameters: {
     *                        entity: oro_class_name(entity, true),
     *                        entityId: entity.id
     *                    }
     *                },
     *                autocomplete_api_accessor: {
     *                    class: 'oroui/js/tools/search-api-accessor',
     *                    search_handler_name: 'tags',
     *                    label_field_name: 'name'
     *                },
     *                editor: {
     *                    view_options: {
     *                        permissions: {
     *                            oro_tag_create: resource_granted('oro_tag_create'),
     *                            oro_tag_unassign_global: resource_granted('oro_tag_unassign_global')
     *                        }
     *                    }
     *                }
     *            }
     *        }
     *    }
     * }) }}></div>
     * ```
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options._sourceElement - Element where to connect this view (passed automatically when
     *                                          page component is [connected through DOM attributes]
     *                                          (../../../../UIBundle/Resources/doc/reference/page-component.md))
     * @param {string} options.frontend_type - frontend type, please find [available keys here]
     *                                          (../../public/js/tools/frontend-type-map.js)
     * @param {*} options.value - value to edit
     * @param {string} options.fieldName - field name to use when sending value to server
     * @param {Object} options.metadata - Editor metadata
     * @param {Object} options.metadata.inline_editing - inline-editing configuration
     *
     * @augments BaseComponent
     * @exports InlineEditableViewComponent
     */
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

    InlineEditableViewComponent = BaseComponent.extend(/** @exports InlineEditableViewComponent.prototype */{
        OVERLAY_TOOL_DEFAULTS: {
            position: {
                my: 'left top',
                at: 'left-10 top-6',
                collision: 'flipfit'
            },
            backdrop: true
        },

        METADATA_DEFAULTS: {
            inline_editing: {
                enable: false,
                save_api_accessor: {
                    'class': 'oroui/js/tools/api-accessor'
                }
            }
        },

        WIDTH_INCREMENT: 15,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options.metadata = $.extend(true, {}, this.METADATA_DEFAULTS, options.metadata);
            this.inlineEditingOptions = options.metadata.inline_editing;
            var waitors = [];
            this.fieldName = options.fieldName || 'value';
            // frontend type mapped to viewer/editor/reader
            var classes = frontendTypeMap[options.frontend_type];
            this.classes = classes;
            this.metadata = options.metadata;
            this.model = new BaseModel();
            this.model.set(this.fieldName, options.value);
            if (this.inlineEditingOptions.enable) {
                this.wrapper = new InlineEditorWrapperView({
                    el: options._sourceElement,
                    autoRender: true
                });
                this.view = new classes.viewer(_.extend({
                    el: this.wrapper.getContainer(),
                    autoRender: true,
                    model: this.model,
                    fieldName: this.fieldName
                }));
                if (this.classes.editor.processMetadata) {
                    waitors.push(this.classes.editor.processMetadata(this.metadata));
                }
                this.wrapper.on('start-editing', this.enterEditMode, this);
                waitors.push(tools.loadModuleAndReplace(this.inlineEditingOptions.save_api_accessor, 'class').then(
                    _.bind(function() {
                        var ConcreteApiAccessor = this.inlineEditingOptions.save_api_accessor['class'];
                        this.saveApiAccessor = new ConcreteApiAccessor(
                            _.omit(this.inlineEditingOptions.save_api_accessor, 'class'));
                    }, this)
                ));
            } else {
                this.view = new classes.viewer(_.extend({
                    el: options._sourceElement,
                    autoRender: true,
                    model: this.model,
                    fieldName: this.fieldName
                }));
            }
            this.deferredInit = $.when.apply($, waitors);
        },

        enterEditMode: function() {
            var View = this.classes.editor;
            var viewConfiguration = this.inlineEditingOptions.editor ?
                this.inlineEditingOptions.editor.view_options :
                {};
            var viewInstance = new View(_.extend({}, viewConfiguration, {
                autoRender: true,
                model: this.model,
                fieldName: this.fieldName,
                metadata: this.metadata
            }));

            this.editorView = viewInstance;

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
            this.overlay.remove();
            this.editorView.dispose();
            delete this.editorView;
        },

        saveCurrentCellAndExit: function() {
            if (this.saveCurrentCell(false)) {
                this.exitEditMode(true);
            }
        },

        saveCurrentCell: function(exit) {
            if (!this.editorView) {
                throw Error('Edit mode disabled');
            }
            if (!this.editorView.isChanged()) {
                return true;
            }
            if (!this.editorView.isValid()) {
                this.editorView.focus();
                return false;
            }
            var wrapper = this.wrapper;
            var serverUpdateData = this.editorView.getServerUpdateData();
            var modelUpdateData = this.editorView.getModelUpdateData();
            wrapper.$el.addClass('loading');
            var ctx = {
                wrapper: wrapper,
                oldState: _.pick(this.model.toJSON(), _.keys(modelUpdateData))
            };
            this.updateModel(this.model, this.editorView, modelUpdateData);
            if (this.saveApiAccessor.initialOptions.field_name) {
                var keys = _.keys(serverUpdateData);
                if (keys.length > 1) {
                    throw new Error('Only single field editors are supported with field_name option');
                }
                var newData = {};
                newData[this.saveApiAccessor.initialOptions.field_name] = serverUpdateData[keys[0]];
                serverUpdateData = newData;
            }
            this.saveApiAccessor.send(this.model.toJSON(), serverUpdateData, {}, {
                    processingMessage: __('oro.form.inlineEditing.saving_progress'),
                    preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
                })
                .done(_.bind(InlineEditableViewComponent.onSaveSuccess, ctx))
                .fail(_.bind(InlineEditableViewComponent.onSaveError, ctx))
                .always(function() {
                    wrapper.$el.removeClass('loading');
                });
            if (exit !== false) {
                this.exitEditMode();
            }
            return true;
        },

        updateModel: function(model, editorView, updateData) {
            // assume "undefined" as delete value request
            for (var key in updateData) {
                if (updateData.hasOwnProperty(key)) {
                    if (updateData[key] === editorView.UNSET_FIELD_VALUE) {
                        model.unset(key);
                        delete updateData[key];
                    }
                }
            }
            model.set(updateData);
        },

        /**
         * Resizes editor to base view width
         */
        resizeTo: function(view, baseView) {
            view.$el.css({
                width: baseView.$el.outerWidth() + this.WIDTH_INCREMENT
            });
        }
    }, {
        onSaveSuccess: function() {
            if (!this.wrapper.disposed && this.wrapper.$el) {
                var _this = this;
                this.wrapper.$el.addClass('save-success');
                _.delay(function() {
                    _this.wrapper.$el.removeClass('save-success');
                }, 2000);
            }
            mediator.execute('showFlashMessage', 'success', __('oro.form.inlineEditing.successMessage'));
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;
            if (!this.wrapper.disposed && this.wrapper.$el) {
                var _this = this;
                this.wrapper.$el.addClass('save-fail');
                _.delay(function() {
                    _this.wrapper.$el.removeClass('save-fail');
                }, 2000);
            }
            this.wrapper.model.set(this.oldState);
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
