define(function(require) {
    'use strict';

    var InlineEditingPlugin;
    var _ = require('underscore');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var Row = require('orodatagrid/js/datagrid/row');

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
            cell.$el.removeClass('view-mode');
            cell.$el.addClass('edit-mode');
            cell.$el.prepend(
                '<div class="inline-editor-wrapper">' +
                    '<div class="input-append">' +
                        '<input type="text" class="form-control">' +
                        '<button class="add-on btn entity-select-btn"><i class="icon-check"></i></button>' +
                        '<button class="add-on btn entity-select-btn"><i class="icon-times"></i></button>' +
                    '</div>' +
                '</div>'
            );
            cell.$('input').val(cell.model.get(cell.column.get('name'))).focus();
        }
    });

    return InlineEditingPlugin;
});
