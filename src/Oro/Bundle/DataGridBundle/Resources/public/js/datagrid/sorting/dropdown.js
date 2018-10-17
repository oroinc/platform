define(function(require) {
    'use strict';

    var SortingDropdown;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var Select2View = require('oroform/js/app/views/select2-view');
    var module = require('module');
    var config = _.defaults(module.config(), {
        hasSortingOrderButton: true,
        inlineSortingLabel: false, // Draws inline label for sorter if soring order button (previous option) is not enabled
        disableNotSelectedOption: false,
        className: 'sorting-select sorting-select-control',
        dropdownClassName: 'sorting-select-control'
    });

    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/toolbar-sorting
     * @class   orodatagrid.datagrid.SortingDropdown
     * @extends Backbone.View
     */
    SortingDropdown = BaseView.extend({
        SEARCH_CAPABILITY_GATE: 8,

        VALUE_SEPARATOR: '-sep-',

        DIRECTIONS: ['ascending', 'descending'],

        /** @property */
        template: require('tpl!orodatagrid/templates/datagrid/sorting-dropdown.html'),

        /** @property */
        events: {
            'change select': 'onChangeSorting',
            'click [data-name=order-toggle]': 'onDirectionToggle'
        },

        className: config.className,

        dropdownClassName: config.dropdownClassName,

        select2Config: {},

        /** @property */
        enabled: true,

        hasSortingOrderButton: config.hasSortingOrderButton,

        inlineSortingLabel: config.inlineSortingLabel,

        disableNotSelectedOption: config.disableNotSelectedOption,

        currentColumn: null,

        currentDirection: null,

        /**
         * @inheritDoc
         */
        constructor: function SortingDropdown() {
            SortingDropdown.__super__.constructor.apply(this, arguments);
        },

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

            _.extend(this, _.pick(options, ['columns', 'collection', 'hasSortingOrderButton']));
            _.extend(this, _.pick(options.collection.options.toolbarOptions, ['disableNotSelectedOption']));

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
                var direction;
                var column = this.columns.find(function(column) {
                    return column.get('name') === columnName;
                });
                switch (parseInt(this.collection.state.sorters[columnName], 10)) {
                    case -1:
                        direction = this.DIRECTIONS[0];
                        break;
                    case 1:
                        direction = this.DIRECTIONS[1];
                        break;
                    default:
                        return;
                }
                this.currentDirection = direction;
                this.currentColumn = column;
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

        _getCurrentValue: function() {
            if (!this.currentColumn) {
                return null;
            } else if (this.hasSortingOrderButton) {
                return this.currentColumn.get('name');
            } else {
                return this.currentColumn.get('name') + this.VALUE_SEPARATOR + this.currentDirection;
            }
        },

        _updateDisplayValue: function() {
            this.$('select').inputWidget('val', this._getCurrentValue());
            if (this.hasSortingOrderButton) {
                this._updateDisplayDirection();
            }
        },

        _updateDisplayDirection: function() {
            this.$('[data-name=order-toggle-icon]')
                .toggleClass('fa-sort-amount-asc', this.currentDirection === this.DIRECTIONS[0])
                .toggleClass('fa-sort-amount-desc', this.currentDirection === this.DIRECTIONS[1]);
        },

        onDirectionToggle: function() {
            if (this.currentDirection === this.DIRECTIONS[1]) {
                this.currentDirection = this.DIRECTIONS[0];
            } else {
                this.currentDirection = this.DIRECTIONS[1];
            }
            if (this.currentColumn) {
                this.collection.trigger('backgrid:sort', this.currentColumn, this.currentDirection);
            }
            this._updateDisplayDirection();
        },

        onChangeSorting: function() {
            var column;
            var columnName;
            var newDirection;
            var value = this.$('select').val();
            if (this.hasSortingOrderButton) {
                columnName = value;
            } else {
                value = value.split(this.VALUE_SEPARATOR);
                columnName = value[0];
                newDirection = value[1];
            }
            column = this.columns.findWhere({name: columnName});

            if (column) {
                if (newDirection) {
                    this.currentDirection = newDirection;
                } else if (!this.currentDirection) {
                    this.currentDirection = this.DIRECTIONS[0];
                }
                this.collection.trigger('backgrid:sort', column, this.currentDirection);
            } else {
                this.currentColumn = null;
                this.currentDirection = null;

                this.collection.trigger('backgrid:sort', '', this.currentDirection);
            }
            if (this.hasSortingOrderButton) {
                this._updateDisplayDirection();
            }
        },

        getTemplateData: function() {
            var data = SortingDropdown.__super__.getTemplateData.apply(this, arguments);
            data = _.extend(data, {
                columns: this._getSelectOptionsData(),
                selectedValue: this._getCurrentValue(),
                currentDirection: this.currentDirection,
                hasSortingOrderButton: this.hasSortingOrderButton,
                disableNotSelectedOption: this.disableNotSelectedOption,
                inlineSortingLabel: this.inlineSortingLabel
            });
            return data;
        },

        _getSelectOptionsData: function() {
            var options = [];
            _.each(_.where(this.columns.toJSON(), {sortable: true, renderable: true}), _.bind(function(column) {
                if (this.hasSortingOrderButton) {
                    options.push({
                        label: column.label,
                        value: column.name
                    });
                } else {
                    _.each(this.DIRECTIONS, _.bind(function(direction) {
                        options.push({
                            label: column.label,
                            sortingType: column.sortingType,
                            directionValue: direction,
                            value: column.name + this.VALUE_SEPARATOR + direction
                        });
                    }, this));
                }
            }, this));

            return options;
        },

        /**
         * Init sorting subview
         */
        initSubview: function() {
            this.subview('select2', new Select2View({
                el: this.$('select'),
                select2Config: this.select2Config
            }));
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

            this.select2Config = {
                dropdownCssClass: _.result(this, 'dropdownClassName'),
                dropdownAutoWidth: true
            };
            var searchCapabilityGate = this.SEARCH_CAPABILITY_GATE;
            if (!this.hasSortingOrderButton) {
                searchCapabilityGate = Math.floor(searchCapabilityGate / this.DIRECTIONS.length);
            }

            if (this.columns.where({sortable: true, renderable: true}).length < searchCapabilityGate) {
                this.select2Config.minimumResultsForSearch = -1;
            }

            this.initSubview();

            this._updateDisplayDirection();

            return this;
        }
    });

    return SortingDropdown;
});
