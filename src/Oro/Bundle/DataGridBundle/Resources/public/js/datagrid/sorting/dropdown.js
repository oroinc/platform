define([
    'jquery',
    'underscore',
    'backbone',
    'tpl!orodatagrid/templates/datagrid/sorting-dropdown.html'
], function($, _, Backbone, template) {
    'use strict';

    var SortingDropdown;

    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/toolbar-sorting
     * @class   orodatagrid.datagrid.SortingDropdown
     * @extends Backbone.View
     */
    SortingDropdown = Backbone.View.extend({
        /** @property */
        template: template,

        /** @property */
        events: {
            'change select': 'onChangeSorting'
        },

        /** @property */
        enabled: true,

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

            this.columns = options.columns;
            this.collection = options.collection;

            this.listenTo(this.columns, 'change:direction', this._selectCurrentSortableColumn);
            this.listenTo(this.columns, 'change:renderable', this._columnRenderableChanged);
            this._initCurrentSortableColumn();

            SortingDropdown.__super__.initialize.call(this, options);
        },

        _initCurrentSortableColumn: function() {
            var keys = Object.keys(this.collection.state.sorters);
            if (keys.length) {
                var columnName = keys[0];
                var direction = null;
                var column = this.columns.find(function(column) {
                    return column.get('name') === columnName;
                });
                var intDirection = this.collection.state.sorters[columnName];
                if (1 === parseInt(intDirection, 10)) {
                    direction = 'descending';
                } else if (-1 === parseInt(intDirection, 10)) {
                    direction = 'ascending';
                }
                if (direction) {
                    this._selectCurrentSortableColumn(column, direction);
                }
            }
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
                this.$('select').val(this._getColumnValue(column, direction));
            }
        },

        /**
         * @param {Object} column
         * @param {string} renderable
         * @private
         */
        _columnRenderableChanged: function(column, renderable) {
            if (!renderable && this.currentColumn === column) {
                this.currentColumn = null;
                this.currentDirection = null;
            }
            this.render();
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
         * @returns {orodatagrid.datagrid.SortingDropdown}
         */
        render: function() {
            if (!this.enabled) {
                return this;
            }
            this.$el.html(this.template({
                columns: _.filter(this.columns.models, function(model) {
                    return model.get('sortable') && model.get('renderable');
                }),
                currentColumn: this.currentColumn,
                currentDirection: this.currentDirection,
                getColumnValue: _.bind(this._getColumnValue, this)
            }));

            return this;
        }
    });

    return SortingDropdown;
});
