/*global define*/
define(['jquery', 'underscore', 'orotranslation/js/translator', 'backgrid'
    ], function ($, _, __, Backgrid) {
    'use strict';

    /**
     * Boolean column cell. Added missing behaviour.
     *
     * @export  orodatagrid/js/datagrid/cell/boolean-cell
     * @class   orodatagrid.datagrid.cell.BooleanCell
     * @extends Backgrid.BooleanCell
     */
    return Backgrid.BooleanCell.extend({
        /** @property {Boolean} */
        listenRowClick: true,

        /**
         * @inheritDoc
         */
        render: function () {
            if (this.column.get('editable')) {
                // render a checkbox for editable cell
                Backgrid.BooleanCell.prototype.render.apply(this, arguments);
            } else {
                // render a yes/no text for non editable cell
                this.$el.empty();
                var text = this.formatter.fromRaw(this.model.get(this.column.get("name"))) ? __('Yes') : __('No');
                this.$el.append('<span>').text(text);
                this.delegateEvents();
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function (e) {
            Backgrid.BooleanCell.prototype.enterEditMode.apply(this, arguments);
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
        onRowClicked: function (row, e) {
            if (!this.$el.is(e.target) && !this.$el.has(e.target).length) {
                // click on another cell of a row
                this.enterEditMode(e);
            }
        }
    });
});
