define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const Backgrid = require('backgrid');

    /**
     * Datagrid footer cell
     *
     * @exports orodatagrid/js/datagrid/footer/footer-cell
     * @class orodatagrid.datagrid.footer.FooterCell
     * @extends Backbone.View
     */
    const FooterCell = Backbone.View.extend({
        optionNames: ['column'],

        _attributes: Backgrid.Cell.prototype._attributes,

        /** @property */
        tagName: 'th',

        /** @property */
        template: _.template(
            // wrap label into span otherwise underscore will not render it
            '<span><%- label  %><%- total ? (label? ": " : "") + total : "" %></span>'
        ),

        keepElement: false,

        /**
         * @inheritdoc
         */
        constructor: function FooterCell(options) {
            FooterCell.__super__.constructor.call(this, options);
        },

        /**
         * Initialize.
         */
        initialize: function(options) {
            this.options = options || {};

            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.listenTo(options.collection, 'reset', this.render);
            this.listenTo(this.column, 'change:editable change:sortable change:renderable',
                function(column) {
                    const changed = column.changedAttributes();
                    for (const key in changed) {
                        if (changed.hasOwnProperty(key)) {
                            this.$el.toggleClass(key, changed[key]);
                        }
                    }
                });
        },

        /**
         * Renders a footer cell.
         *
         * @return {*}
         */
        render: function() {
            this.$el.empty();
            const columnName = this.column.get('name');
            const state = this.collection.state || {};
            const totals = state.totals || {};

            if (_.isUndefined(totals[this.options.rowName])) {
                this.$el.removeClass('renderable');
                return;
            }
            if (!_.isUndefined(totals[this.options.rowName]) &&
                _.has(totals[this.options.rowName].columns, columnName)) {
                const columnTotals = totals[this.options.rowName].columns[columnName];
                if (!columnTotals.label && !columnTotals.total) {
                    return this;
                }
                this.$el.append(this.template({
                    label: columnTotals.label,
                    total: columnTotals.total
                }));
            }

            if (!_.isUndefined(this.column.attributes.cell.prototype.className)) {
                const className = this.column.attributes.cell.prototype.className;
                this.$el.addClass(_.isFunction(className) ? className.call(this) : className);
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
