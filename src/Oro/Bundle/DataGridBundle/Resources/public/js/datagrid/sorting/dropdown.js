define(function(require) {
    'use strict';

    var SortingDropdown;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var Select2View = require('oroform/js/app/views/select2-view');
    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/toolbar-sorting
     * @class   orodatagrid.datagrid.SortingDropdown
     * @extends Backbone.View
     */
    SortingDropdown = BaseView.extend({
        /** @property */
        template: require('tpl!orodatagrid/templates/datagrid/sorting-dropdown.html'),

        noWrap: false,

        /** @property */
        events: {
            'change select': 'onChangeSorting',
            'click [data-name=order-toggle]': 'onDirectionToggle'
        },

        className: 'sorting-select-control',

        /** @property */
        enabled: true,

        currentColumn: null,

        currentDirection: null,

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
            this.listenTo(this.columns, 'change:sortable', this._columnSortableChanged);
            this.listenTo(this.collection, 'updateState', this.render);
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
                this.currentDirection = direction;
                this.currentColumn = column;
                this._updateDisplayValue();
            }
        },

        /**
         * @param {Object} column
         * @param {boolean} renderable
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
         * @param {boolean} sortable
         * @private
         */
        _columnSortableChanged: function(column, sortable) {
            if (!sortable && this.currentColumn === column) {
                this.currentColumn = null;
                this.currentDirection = null;
            }
            this.render();
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

        _updateDisplayValue: function() {
            this.$('select').select2('val', this.currentColumn ? this.currentColumn.get('name') : null);
            this._updateDisplayDirection();
        },

        _updateDisplayDirection: function() {
            this.$('[data-name=order-toggle]')
                .toggleClass('icon-sort-by-attributes', this.currentDirection === 'ascending')
                .toggleClass('icon-sort-by-attributes-alt', this.currentDirection === 'descending');
        },

        onDirectionToggle: function() {
            if (this.currentDirection === 'descending') {
                this.currentDirection = 'ascending';
            } else {
                this.currentDirection = 'descending';
            }
            this._updateDisplayValue();
            this.onChangeSorting();
        },

        onChangeSorting: function() {
            var value = this.$('select').val();
            var column = this.columns.findWhere({'name': value});
            if (column) {
                if (!this.currentDirection) {
                    this.currentDirection = 'ascending';
                    this._updateDisplayDirection();
                }
                this.collection.trigger('backgrid:sort', column, this.currentDirection);
            } else {
                this.currentDirection = null;
                this._updateDisplayDirection();
            }
        },

        getTemplateData: function() {
            var data = SortingDropdown.__super__.getTemplateData.apply(this, arguments);
            data = _.extend(data, {
                columns: _.where(this.columns.toJSON(), {sortable: true, renderable: true}),
                currentColumn: this.currentColumn,
                currentDirection: this.currentDirection
            });

            return data;
        },

        /**
         * @returns {orodatagrid.datagrid.SortingDropdown}
         */
        render: function() {
            this._initCurrentSortableColumn();
            if (!this.enabled) {
                return this;
            }
            SortingDropdown.__super__.render.call(this);

            this.subview('select2', new Select2View({
                el: this.$('select'),
                select2Config: {
                    dropdownCssClass: _.result(this, 'className'),
                    dropdownAutoWidth: true
                }
            }));

            this._updateDisplayDirection();

            return this;
        },
    });

    return SortingDropdown;
});
