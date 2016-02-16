define([
    'jquery',
    'underscore',
    'backbone'
], function($, _, Backbone) {
    'use strict';

    var ToolbarSorting;

    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/toolbar-sorting
     * @class   orodatagrid.datagrid.ToolbarSorting
     * @extends Backbone.View
     */
    ToolbarSorting = Backbone.View.extend({
        /** @property */
        template: '#template-datagrid-toolbar-sorting',

        /** @property */
        events: {
            'change select': 'onChangeSorting'
        },

        /** @property */
        enabled: false,

        currentColumn: null,

        currentDirection: null,

        VALUE_SEPARATOR: '-sep-',

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Array} [options.items]
         */
        initialize: function(options) {
            options = options || {};

            if (!options.columns) {
                throw new TypeError('"columns" is required');
            }

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            if (!options.enabled) {
                return;
            }

            this.enabled = true;

            this.columns = options.columns;
            this.collection = options.collection;

            this.listenTo(this.columns, 'change:direction', this._selectCurrentSortableColumn);
            this.template = _.template($(this.template).html());

            ToolbarSorting.__super__.initialize.call(this, options);
        },

        /**
         * @param {Object} column
         * @param {string} direction
         * @private
         */
        _selectCurrentSortableColumn: function(column, direction) {
            if (direction !== null) {
                this.currentColumn = column;
                this.currentDirection = direction;
                this.render();
            }
        },

        /**
         * @param {Object} column
         * @param {string} direction
         * @returns {string}
         * @private
         */
        _getColumnValue: function(column, direction) {
            return column.get('name') + this.VALUE_SEPARATOR + direction;
        },

        /**
         * @param {string} value
         * @returns {*}
         * @private
         */
        _getColumnByValue: function(value) {
            var name = value.split(this.VALUE_SEPARATOR)[0];
            for (var i = 0; i < this.columns.models.length; i++) {
                if (this.columns.models[i].get('name') === name) {
                    return this.columns.models[i];
                }
            }
        },

        /**
         * @param {string} value
         * @returns {*}
         * @private
         */
        _getDirectionByValue: function(value) {
            value = value.split(this.VALUE_SEPARATOR);
            if (value.length === 2) {
                return value[1];
            }
        },

        /**
         * @return {*}
         */
        disable: function() {
            this.enabled = false;
            this.render();
            return this;
        },

        /**
         * @return {*}
         */
        enable: function() {
            this.enabled = true;
            this.render();
            return this;
        },

        /**
         * @param {Event} e
         */
        onChangeSorting: function(e) {
            var column;
            e.preventDefault();
            var value = $(e.target).val();
            if (value) {
                column = this._getColumnByValue(value);
                if (column) {
                    this.collection.trigger('backgrid:sort', column, this._getDirectionByValue(value));
                }
            }
        },

        /**
         * @returns {orodatagrid.datagrid.ToolbarSorting}
         */
        render: function() {
            if (!this.enabled) {
                return this;
            }
            this.$el.empty();

            this.$el.append($(this.template({
                columns: _.filter(this.columns.models, function(model) {
                    return model.get('sortable');
                }),
                currentColumn: this.currentColumn,
                currentDirection: this.currentDirection,
                getColumnValue: this._getColumnValue
            })));

            return this;
        }
    });

    return ToolbarSorting;
});
