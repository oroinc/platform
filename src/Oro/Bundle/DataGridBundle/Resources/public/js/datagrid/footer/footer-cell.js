/* global define */
define(['jquery', 'underscore', 'backbone', 'backgrid'],
function ($, _, Backbone, Backgrid) {
    "use strict";

    /**
     * Datagrid footer cell
     *
     * @export  oro/datagrid/footer
     * @class   FooterCell
     * @extends Backbone.View
     */
    return Backgrid.FooterCell = Backbone.View.extend({
        /** @property */
        tagName: "th",

        /** @property */
        template:_.template(
            //'<span><%= label %><%= total ? ": " + total : "" %></span>' // wrap label into span otherwise underscore will not render it
            '<span><%= label %>: <%= total  %></span>' // wrap label into span otherwise underscore will not render it
        ),

        /**
         * Initialize.
         */
        initialize: function (options) {
            Backgrid.requireOptions(options, ["column", "collection"]);

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }
        },

        /**
         * Renders a footer cell.
         *
         * @return {*}
         */
        render: function () {
            this.$el.empty();

            var columnName = this.column.get('name'),
                state = this.collection.state,
                totals = this.collection.state.totals;

            console.log(totals);

            if (_.has(state.totals, columnName)) {

                var columnTotals = state.totals[columnName];

                console.log('*********************************');
                console.log(columnName, state.totals, _.omit(state.totals, columnName).name );

                this.$el.append($(this.template({
                    label: columnTotals.label,
                    total: columnTotals.total + ' test'
                })));
            }

            this.$el[0].style.backgroundColor = '#f2f2f2';
            return this;
        }
    });
});
