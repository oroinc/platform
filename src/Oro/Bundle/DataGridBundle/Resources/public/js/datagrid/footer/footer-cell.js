/*global define*/
define(['jquery', 'underscore', 'backbone', 'backgrid'
    ], function ($, _, Backbone, Backgrid) {
    "use strict";

    /**
     * Datagrid footer cell
     *
     * @exports orodatagrid/js/datagrid/footer/footer-cell
     * @class orodatagrid.datagrid.footer.FooterCell
     * @extends Backbone.View
     */
    return Backgrid.FooterCell = Backbone.View.extend({
        /** @property */
        tagName: "th",

        /** @property */
        template: _.template(
            '<span><%= label  %><%= total ? (label? ": " : "") + total : "" %></span>' // wrap label into span otherwise underscore will not render it
        ),

        /**
         * Initialize.
         */
        initialize: function (options) {
            this.options = options || {};
            Backgrid.requireOptions(options, ["column", "collection"]);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.listenTo(options.collection, "reset", this.render);
        },

        /**
         * Renders a footer cell.
         *
         * @return {*}
         */
        render: function () {
            this.$el.empty();
            var columnName = this.column.get('name'),
                state      = this.collection.state || {},
                totals     = state.totals || {};

            if (_.isUndefined(totals[this.options.rowName])){
                this.$el.hide();
                return;
            }
            if (!_.isUndefined(totals[this.options.rowName]) && _.has(totals[this.options.rowName].columns, columnName)) {
                this.$el.show();
                var columnTotals = totals[this.options.rowName].columns[columnName];
                if (!columnTotals.label && !columnTotals.total) {
                    return this;
                }
                this.$el.append($(this.template({
                    label: columnTotals.label,
                    total: columnTotals.total
                })));
            }

            if (!_.isUndefined(this.column.attributes.cell.prototype.className)) {
                this.$el.addClass(this.column.attributes.cell.prototype.className);
            }

            if (this.column.has('align')) {
                this.$el.css('text-align', this.column.get('align'));
            }

            return this;
        }
    });
});
