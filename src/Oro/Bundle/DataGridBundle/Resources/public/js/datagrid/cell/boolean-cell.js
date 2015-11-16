define([
    'backgrid',
    'orotranslation/js/translator'
], function(Backgrid, __) {
    'use strict';

    var BooleanCell;

    /**
     * Boolean column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/boolean-cell
     * @class   oro.datagrid.cell.BooleanCell
     * @extends Backgrid.BooleanCell
     */
    BooleanCell = Backgrid.BooleanCell.extend({
        /** @property {Boolean} */
        listenRowClick: true,

        events: {
            'change :checkbox': 'onChange'
        },

        /**
         * @inheritDoc
         */
        render: function() {
            if (this.column.get('editable')) {
                // render a checkbox for editable cell
                BooleanCell.__super__.render.apply(this, arguments);
                var state = {selected: this.model.get(this.column.get("name"))};
                this.model.trigger('backgrid:select', this.model, state.selected);
            } else {
                // render a yes/no text for non editable cell
                this.$el.empty();
                var text = this.formatter.fromRaw(this.model.get(this.column.get('name'))) ? __('Yes') : __('No');
                this.$el.append('<span>').text(text);
                this.delegateEvents();
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function(e) {
            BooleanCell.__super__.enterEditMode.apply(this, arguments);
            if (this.column.get('editable')) {
                var $editor = this.currentEditor.$el;
                $editor.prop('checked', !$editor.prop('checked')).change();
                e.stopPropagation();
            }
        },

        /**
         * @param {Backgrid.Row} row
         * @param {Event} e
         */
        onRowClicked: function(row, e) {
            if (!this.$el.is(e.target) && !this.$el.has(e.target).length) {
                // click on another cell of a row
                this.enterEditMode(e);
                this.model.trigger('backgrid:select', this.model, this.model.get(this.column.get("name")));
            }
        },

        /**
         * When the checkbox's value changes, this method will trigger a Backbone
         * `backgrid:selected` event with a reference of the model and the
         * checkbox's `checked` value.
         */
        onChange: function(e) {
            this.model.trigger('backgrid:select', this.model, $(e.target).is(':checked'));
        },

        onClick: function () {
            this.model.trigger('backgrid:select', this.model, this.model.get(this.column.get("name")));
        }
    });

    return BooleanCell;
});
