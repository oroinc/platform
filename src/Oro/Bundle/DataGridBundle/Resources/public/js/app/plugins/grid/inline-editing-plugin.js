define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var $ = require('jquery');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var Row = require('orodatagrid/js/datagrid/row');
    var TextEditorComponent = require('orodatagrid/js/app/components/editor/text-editor-component');

    require('oroui/lib/jquery/jquery.disablescroll');

    InlineEditingPlugin = BasePlugin.extend({
        enable: function() {
            var originalMakeCell = Row.prototype.makeCell;
            this.originalMakeCell = originalMakeCell;
            var _this = this;
            Row.prototype.makeCell = function() {
                var cell = originalMakeCell.apply(this, arguments);
                var originalRender = cell.render;
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

                return cell;
            };
            InlineEditingPlugin.__super__.enable.call(this);
        },

        disable: function() {
            InlineEditingPlugin.__super__.disable.call(this);
        },

        enterEditMode: function(cell) {
            var _this = this;
            this.prepareEnvironmentForEditing(cell);
            cell.$el.prepend(
                '<div class="inline-editor-wrapper"></div>'
            );
            var editorComponent = new TextEditorComponent({
                data: {
                    value: cell.model.get(cell.column.get('name'))
                },
                _sourceElement: cell.$('.inline-editor-wrapper')
            });

            editorComponent.on('saveAction', function() {
                _this.restoreEnvironment(cell);
            });
            editorComponent.on('cancelAction', function() {
                _this.restoreEnvironment(cell);
            });
        },

        prepareEnvironmentForEditing: function(cell) {
            $('body').addClass('backdrop');
            cell.$el.removeClass('view-mode');
            cell.$el.addClass('edit-mode');
            cell.$('.inline-editor-wrapper').click(function(e) {
                e.stopPropagation();
            });
            cell.$el.parents('.grid-scrollable-container').disablescroll();
            cell.$el.parents('.grid').removeClass('table-hover');
        },

        restoreEnvironment: function(cell) {
            $('body').removeClass('backdrop');
            cell.$el.addClass('view-mode');
            cell.$el.removeClass('edit-mode');
            cell.$('.inline-editor-wrapper').remove();
            cell.$el.parents('.grid-scrollable-container').disablescroll('undo');
            cell.$el.parents('.grid').addClass('table-hover');
        }
    });

    return InlineEditingPlugin;
});
