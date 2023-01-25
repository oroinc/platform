define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Backgrid = require('backgrid/lib/backgrid');

    Backgrid.Cell.prototype.optionNames = ['column'];

    /**
     * Cells should be removed durung dispose cycle
     */
    Backgrid.Cell.prototype.keepElement = false;

    /**
     * Copied from backgrid. Removed unused in our project code which slow downs rendering
     */
    Backgrid.Cell.prototype.initialize = function(options) {
        /*
        // Columns are always prepared in Oro.Datagrid
        if (!(this.column instanceof Column)) {
            this.column = new Column(this.column);
        }
        */

        const column = this.column;
        const model = this.model;
        const $el = this.$el;

        let Formatter = Backgrid.resolveNameToClass(column.get('formatter') ||
            this.formatter, 'Formatter');

        if (!_.isFunction(Formatter.fromRaw) && !_.isFunction(Formatter.toRaw)) {
            Formatter = new Formatter();
        }

        this.formatter = Formatter;

        this.editor = typeof this.column.get('editor') === 'function'
            ? this.column.get('editor')
            : Backgrid.resolveNameToClass(this.editor, 'CellEditor');

        this.listenTo(model, 'change:' + column.get('name'), function() {
            if (!$el.hasClass('editor')) {
                this.render();
                this._setAttributes(this._collectAttributes());
            }
        });

        this.listenTo(model, 'backgrid:error', this.renderError);

        this.listenTo(column, 'change:editable change:sortable change:renderable',
            function(column) {
                const changed = column.changedAttributes();
                for (const key in changed) {
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
     Render a text string in a table cell. The text is converted from the
     model's raw value for this cell's column.
     */
    Backgrid.Cell.prototype.render = function() {
        const $el = this.$el;
        $el.empty();
        const model = this.model;
        const columnName = this.column.get('name');
        $el.text(this.formatter.fromRaw(model.get(columnName), model));
        // $el.addClass(columnName);
        // this.updateStateClassesMaybe();
        this.delegateEvents();
        return this;
    };

    /**
     * Event binding on each cell gives perfomance slow down
     *
     * Please find support code in ../datagrid/row.js
     */
    Backgrid.Cell.prototype.delegatedEventBinding = true;
    const oldDelegateEvents = Backgrid.Cell.prototype.delegateEvents;
    Backgrid.Cell.prototype.delegateEvents = function() {
        if (_.isFunction(this.events)) {
            oldDelegateEvents.call(this);
        }
    };
    const oldUndelegateEvents = Backgrid.Cell.prototype.undelegateEvents;
    Backgrid.Cell.prototype.undelegateEvents = function() {
        if (_.isFunction(this.events)) {
            oldUndelegateEvents.call(this);
        }
    };

    /**
     * Shortcut method for the check if the cell is editable
     *
     * @return {boolean}
     */
    Backgrid.Cell.prototype.isEditableColumn = function() {
        return Backgrid.callByNeed(this.column.editable(), this.column, this.model);
    };

    Backgrid.Cell.prototype._attributes = function() {
        const attrs = {};
        const {collection} = this.column || {};

        if (collection && collection.length) {
            const rowIndex = collection.indexOf(this.column);
            if (rowIndex !== -1 && (!this.model || this.model.get('isAuxiliary') !== true)) {
                attrs['aria-colindex'] = rowIndex + 1;
            }
        }

        if (
            this.model && this.model.get('isAuxiliary') !== true &&
            this.column.get('notMarkAsBlank') !== true && this.notMarkAsBlank !== true
        ) {
            const value = this.model && this.model.get(this.column.get('name'));

            if (
                value === void 0 ||
                value === null ||
                (_.isString(value) && value.trim().length === 0) ||
                (_.isArray(value) && value.length === 0)
            ) {
                attrs['aria-label'] = __('oro.datagrid.cell.blank.aria_label');
                attrs['data-blank-content'] = __('oro.datagrid.cell.blank.placeholder');
            } else {
                attrs['aria-label'] = null;
                attrs['data-blank-content'] = null;
            }
        }

        return attrs;
    };

    Backgrid.HeaderCell.prototype.optionNames = ['column'];

    Backgrid.HeaderCell.prototype._attributes = function() {
        const attrs = {};

        if (this.column && this.column.get('label')) {
            const {collection} = this.column;
            if (collection && collection.length) {
                const rowIndex = collection.indexOf(this.column);
                if (rowIndex !== -1) {
                    attrs['aria-colindex'] = rowIndex + 1;
                }
            }
        }

        return attrs;
    };

    Backgrid.BooleanCellEditor.prototype.attributes = {
        type: 'checkbox'
    };

    Backgrid.BooleanCellEditor.prototype.initialize = function(options) {
        this.formatter = options.formatter;
        this.column = options.column;
        if (!(this.column instanceof Backgrid.Column)) {
            this.column = new Backgrid.Column(this.column);
        }
    };

    Backgrid.BooleanCellEditor.prototype.saveOrCancel = function(e) {
        const model = this.model;
        const column = this.column;
        const formatter = this.formatter;
        const command = new Backgrid.Command(e);
        // skip ahead to `change` when space is pressed
        if (command.passThru() && e.type !== 'change') return true;
        if (command.cancel()) {
            e.stopPropagation();
            model.trigger('backgrid:edited', model, column, command);
        }

        const $el = this.$el;
        if (command.save()) {
            e.preventDefault();
            e.stopPropagation();
            const val = formatter.toRaw($el.prop('checked'), model);
            model.set(column.get('name'), val);
            model.trigger('backgrid:edited', model, column, command);
        } else if (e.type === 'change') {
            const val = formatter.toRaw($el.prop('checked'), model);
            model.set(column.get('name'), val);
            $el.focus();
        }
    };

    return Backgrid;
});
