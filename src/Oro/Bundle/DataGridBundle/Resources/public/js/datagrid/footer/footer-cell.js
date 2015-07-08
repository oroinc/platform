define([
    'underscore',
    'backbone',
    'backgrid'
], function(_, Backbone, Backgrid) {
    'use strict';

    var FooterCell;

    /**
     * Datagrid footer cell
     *
     * @exports orodatagrid/js/datagrid/footer/footer-cell
     * @class orodatagrid.datagrid.footer.FooterCell
     * @extends Backbone.View
     */
    FooterCell = Backbone.View.extend({
        /** @property */
        tagName: 'th',

        /** @property */
        template: _.template(
            // wrap label into span otherwise underscore will not render it
            '<span><%= label  %><%= total ? (label? ": " : "") + total : "" %></span>'
        ),

        /**
         * Initialize.
         */
        initialize: function(options) {
            this.options = options || {};

            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.listenTo(options.collection, 'reset', this.render);
        },

        /**
         * Renders a footer cell.
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            var columnName = this.column.get('name');
            var state      = this.collection.state || {};
            var totals     = state.totals || {};

            if (_.isUndefined(totals[this.options.rowName])) {
                this.$el.hide();
                return;
            }
            if (!_.isUndefined(totals[this.options.rowName]) &&
                _.has(totals[this.options.rowName].columns, columnName)) {
                this.$el.show();
                var columnTotals = totals[this.options.rowName].columns[columnName];
                if (!columnTotals.label && !columnTotals.total) {
                    return this;
                }
                this.$el.append(this.template({
                    label: columnTotals.label,
                    total: columnTotals.total
                }));
            }

            if (!_.isUndefined(this.column.attributes.cell.prototype.className)) {
                this.$el.addClass(this.column.attributes.cell.prototype.className);
            }

            if (this.column.has('align')) {
                this.$el.removeClass('align-left align-center align-right');
                this.$el.addClass('align-' + this.column.get('align'));
            }

            return this;
        }
    });

    return FooterCell;
});
