define(function(require) {
    'use strict';

    var SortingSelect;
    var _ = require('underscore');
    var SortingDropdown = require('./dropdown');
    var Select2View = require('oroform/js/app/views/select2-view');
    require('jquery.select2');

    SortingSelect = SortingDropdown.extend({
        /** @property */
        template: require('tpl!orodatagrid/templates/datagrid/sorting-select.html'),

        noWrap: false,

        className: 'sorting-select-control',

        events: {
            'change select': 'onChangeSorting',
            'click [data-name=order-toggle]': 'onDirectionToggle'
        },

        _selectCurrentSortableColumn: function(column, direction) {
            if (direction !== null) {
                this.currentDirection = direction;
                this.currentColumn = column;
                this._updateDisplayValue();
            }
        },

        getTemplateData: function() {
            var data = SortingSelect.__super__.getTemplateData.apply(this, arguments);
            data = _.extend(data, {
                columns: _.where(this.columns.toJSON(), {sortable: true, renderable: true}),
                currentDirection: this.currentDirection
            });

            return data;
        },

        render: function() {
            this._initCurrentSortableColumn();
            if (!this.enabled) {
                return this;
            }
            SortingSelect.__super__.render.call(this);

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

        onChangeSorting: function(e) {
            var value = this.$('select').val();
            var column = value ? this._getColumnByValue(value) : false;
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
        }
    });

    return SortingSelect;
});

