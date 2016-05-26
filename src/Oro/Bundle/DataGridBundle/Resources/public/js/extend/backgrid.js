define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backgrid = require('orodatagrid/lib/backgrid/backgrid');

    /**
     * Cells should be removed durung dispose cycle
     */
    Backgrid.Cell.prototype.keepElement = false;

    /**
     * Copied from backgrid. Removed unused in our project code which slow downs rendering
     */
    Backgrid.Cell.prototype.initialize = function(options) {
        this.column = options.column;
        /*
        // Columns are always prepared in Oro.Datagrid
        if (!(this.column instanceof Column)) {
            this.column = new Column(this.column);
        }
        */

        var column = this.column;
        var model = this.model;
        var $el = this.$el;

        var Formatter = Backgrid.resolveNameToClass(column.get('formatter') ||
            this.formatter, 'Formatter');

        if (!_.isFunction(Formatter.fromRaw) && !_.isFunction(Formatter.toRaw)) {
            Formatter = new Formatter();
        }

        this.formatter = Formatter;

        this.editor = Backgrid.resolveNameToClass(this.editor, 'CellEditor');

        this.listenTo(model, 'change:' + column.get('name'), function() {
            if (!$el.hasClass('editor')) {
                this.render();
            }
        });

        this.listenTo(model, 'backgrid:error', this.renderError);

        this.listenTo(column, 'change:editable change:sortable change:renderable',
            function(column) {
                var changed = column.changedAttributes();
                for (var key in changed) {
                    if (changed.hasOwnProperty(key)) {
                        $el.toggleClass(key, changed[key]);
                    }
                }
            });
        /*
        // These three lines give performance slow down
        if (Backgrid.callByNeed(column.editable(), column, model)) $el.addClass('editable');
        if (Backgrid.callByNeed(column.sortable(), column, model)) $el.addClass('sortable');
        if (Backgrid.callByNeed(column.renderable(), column, model)) $el.addClass('renderable');
        */
    };

    /**
     * Event binding on each cell gives perfomance slow down
     *
     * Please find support code in ../datagrid/row.js
     */
    Backgrid.Cell.prototype.delegatedEventBinding = true;
    var oldDelegateEvents = Backgrid.Cell.prototype.delegateEvents;
    Backgrid.Cell.prototype.delegateEvents = function() {
        if (_.isFunction(this.events)) {
            oldDelegateEvents.call(this);
        }
    };
    var oldUndelegateEvents = Backgrid.Cell.prototype.undelegateEvents;
    Backgrid.Cell.prototype.undelegateEvents = function() {
        if (_.isFunction(this.events)) {
            oldUndelegateEvents.call(this);
        }
    };

    return Backgrid;
});
