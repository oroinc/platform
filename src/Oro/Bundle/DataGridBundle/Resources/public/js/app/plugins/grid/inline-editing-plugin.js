define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var Backbone = require('backbone');
    var mediator = require('oroui/js/mediator');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var RouteModel = require('oroui/js/app/models/route-model');
    var TextEditorComponent = require('orodatagrid/js/app/components/editor/text-editor-component');
    var overlayTool = require('oroui/js/tools/overlay');

    require('oroui/lib/jquery/jquery.disablescroll');

    InlineEditingPlugin = BasePlugin.extend({

        /**
         * true if any cell is in edit mode
         *
         * @type {boolean}
         */
        editModeEnabled: false,

        initialize: function() {
            this.listenTo(this.main, 'beforeParseOptions', this.onBeforeParseOptions);
            InlineEditingPlugin.__super__.initialize.apply(this, arguments);
        },

        enable: function() {
            this.listenTo(this.main, 'afterMakeCell', this.onAfterMakeCell);
            InlineEditingPlugin.__super__.enable.call(this);
        },

        onAfterMakeCell: function(row, cell) {
            var originalRender = cell.render;
            var _this = this;
            cell.render = function() {
                var result = originalRender.apply(this, arguments);
                if (this.column.get('editable')) {
                    this.$el.addClass('editable view-mode');
                    this.$el.append('<i class="icon-edit hide-text">Edit</i>');
                }
                return result;
            };
            cell.events = _.extend({}, cell.events, {
                'dblclick': function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    _this.enterEditMode(cell);
                },
                'click .icon-edit': function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    _this.enterEditMode(cell);
                }
            });

            delete cell.events.click;
        },

        /**
         * Copies validation options to columns from metadata
         *
         * @param options
         */
        onBeforeParseOptions: function(options) {
            var columnsMetadata = options.metadata.columns;
            var columns = options.columns;
            for (var i = 0; i < columns.length; i++) {
                var column = columns[i];
                var columnMetadata = _.findWhere(columnsMetadata, {name: column.name});
                if (columnMetadata) {
                    column.validationRules = columnMetadata.validation_rules;
                }
            }
            this.route = new RouteModel({
                routeName: options.metadata.inline_editing.route_name
            });
            this.httpMethod = options.metadata.inline_editing.http_method || 'PATCH';
        },

        enterEditMode: function(cell) {
            if (this.editModeEnabled) {
                this.exitEditMode();
            }
            this.editModeEnabled = true;
            var _this = this;

            var dataModel = new Backbone.Model({
                value: cell.model.get(cell.column.get('name')),
                validationRules: cell.column.get('validationRules')
            });

            var editorComponent = new TextEditorComponent({
                model: dataModel,
                _sourceElement: $('<form class="inline-editor-wrapper"></form>')
            });

            this.dataModel = dataModel;
            this.editorComponent = editorComponent;

            overlayTool.createOverlay(editorComponent.view.$el, {
                position: {
                    my: 'left top',
                    at: 'left-5 top-8',
                    of: cell.$el,
                    collision: 'flipfit'
                }
            });

            editorComponent.on('saveAction', function() {
                cell.$el.addClass('loading');
                var ctx = {
                    cell: cell
                };
                _this.sendRequest(dataModel, cell)
                    .done(_.bind(_this.onSaveSuccess, ctx))
                    .fail(_.bind(_this.onSaveError, ctx))
                    .always(function() {
                        cell.$el.removeClass('loading');
                    });
                _this.exitEditMode();
            });
            editorComponent.on('cancelAction', function() {
                _this.exitEditMode();
            });
        },

        exitEditMode: function() {
            this.editModeEnabled = false;
            overlayTool.removeOverlay(this.editorComponent.view.$el);
            this.dataModel.dispose();
            this.editorComponent.dispose();
            delete this.dataModel;
            delete this.editorComponent;
        },

        sendRequest: function(dataModel, cell) {
            var data = {};
            data[cell.column.get('name')] = dataModel.get('value');
            cell.model.set(data);
            return $.ajax({
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                type: this.httpMethod,
                url: this.route.getUrl(cell.model.toJSON()),
                data: JSON.stringify(data)
            });
        },

        onSaveSuccess: function() {
            if (!this.cell.disposed && this.cell.$el) {
                var _this = this;
                this.cell.$el.addClass('successfully-saved');
                _.delay(function() {
                    _this.cell.$el.removeClass('successfully-saved');
                }, 2000);
            }
            mediator.execute('showFlashMessage', 'success', __('oro.datagrid.inlineEditing.successMessage'));
        },

        onSaveError: function() {
            // placeholder for now
            mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
        }
    });

    return InlineEditingPlugin;
});
