define([
    'jquery',
    'underscore',
    'backgrid'
], function($, _, Backgrid) {
    'use strict';

    var Row;
    var document = window.document;

    /**
     * Grid row.
     *
     * Triggers events:
     *  - "clicked" when row is clicked
     *
     * @export  orodatagrid/js/datagrid/row
     * @class   orodatagrid.datagrid.Row
     * @extends Backgrid.Row
     */
    Row = Backgrid.Row.extend({

        /** @property */
        events: {
            'mousedown': 'onMouseDown',
            'mouseleave': 'onMouseLeave',
            'mouseup': 'onMouseUp',
            'click': 'onClick'
        },

        DOUBLE_CLICK_WAIT_TIMEOUT: 170,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            Row.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
            this.listenTo(this.model, "backgrid:isSelected", this.onBackgridIsSelected);
            this.listenTo(this.model, "backgrid:selected", this.onBackgridSelected);
            this.listenTo(this.model, "change:hasContact", this.onChangeHasContact);

            this._initializeRenderEvents();
        },

        /**
         * Initialize and wrap render events.
         * Is helps to catch 'beforeRender|render|afterRender' events
         *
         * @private
         */
        _initializeRenderEvents: function () {
            var _this = this;
            _.bindAll(this, 'onBeforeRender', 'render', 'onAfterRender');
            this.render = _.wrap(this.render, function(render) {
                _this.onBeforeRender();
                render();
                _this.onAfterRender();
                return _this;
            });
        },

        /**
         * Before render event callback
         */
        onBeforeRender: function () {

        },

        /**
         * After render event callback
         */
        onAfterRender: function () {
            this._setHighlightSelectedRows();
        },

        /**
         * Set highlight selected rows
         * @private
         */
        _setHighlightSelectedRows: function () {
            var _this = this;

            // Get first checkbox
            var checkbox = this.$el.find('td:eq(0)').find('input[type=checkbox]');

            // Exit when checkbox is not found
            // Exit when the model does not have a hasContact attribute
            if (!checkbox || this.model.get('hasContact') === undefined)
                return;

            // Set highlight selected row
            this.toggleSelectedRow(this.model.get('hasContact'));
        },


        /**
         * Handles columns sort event and updates order of cells
         */
        updateCellsOrder: function() {
            var cell;
            var fragment = document.createDocumentFragment();

            for (var i = 0; i < this.columns.length; i++) {
                cell = _.find(this.cells, {column: this.columns.at(i)});
                if (cell) {
                    fragment.appendChild(cell.el);
                }
            }

            this.$el.html(fragment);
            this.trigger('columns:reorder');
        },

        /**
         * Handles row "backgrid:isSelected" event
         *
         * @param {Backbone.Model} model
         * @param {Object} state - state of row
         *  {
         *      selected: true,// or false
         *  }
         */
        onBackgridIsSelected: function (model, state) {
            this.toggleSelectedRow(state.selected);

        },

        /**
         * Handles row "backgrid:selected" event
         *
         * @param model
         * @param isChecked
         */
        onBackgridSelected: function (model, isChecked) {
            this.toggleSelectedRow(isChecked);
        },

        /**
         * Handles row "backgrid:hasContact" event
         *
         * @param {Backbone.Model} model
         * @param {Boolean} hasContact
         */
        onChangeHasContact: function (model, hasContact) {
            this.toggleSelectedRow(hasContact);
        },

        /**
         * Toggle selected row
         *
         * @param {Boolean} isSelected
         */
        toggleSelectedRow: function (isSelected) {
            if (isSelected)
                this.$el.addClass("row-selected");
            else
                this.$el.removeClass("row-selected");
        },

        className: function() {
            return this.model.get('row_class_name');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.clickTimeout) {
                clearTimeout(this.clickTimeout);
            }
            _.each(this.cells, function(cell) {
                cell.dispose();
            });
            delete this.cells;
            delete this.columns;
            Row.__super__.dispose.call(this);
        },

        onMouseDown: function(e) {
            if (this.clickTimeout) {
                // if timeout is set, it means that user makes double click
                clearTimeout(this.clickTimeout);
                delete this.clickTimeout;
                // prevent second click handler launch
                this.mouseDownSelection = null;
                this.mouseDownTarget = null;
                // prevent text selection on double click
                if ($(e.target).closest('.prevent-text-selection-on-dblclick').length) {
                    e.preventDefault();
                }
                return;
            }
            // remember selection and target
            this.mouseDownSelection = this.getSelectedText();
            this.mouseDownTarget = $(e.target).closest('td');
            this.$el.addClass('mouse-down');
        },

        onMouseLeave: function(e) {
            this.$el.removeClass('mouse-down');
        },

        onMouseUp: function(e) {
            this.clickPermit = false;
            // remember selection and target
            var exclude = 'a, .dropdown, .skip-row-click';
            var $target = this.$(e.target);
            // if the target is an action element, skip toggling the email
            if ($target.is(exclude) || $target.parents(exclude).length) {
                return;
            }

            if (this.mouseDownSelection !== this.getSelectedText()) {
                return;
            }

            if (this.mouseDownTarget[0] !== $target.closest('td')[0]) {
                return;
            }

            this.clickPermit = true;
        },

        onClick: function(e) {
            var _this = this;
            if (this.clickPermit) {
                this.clickTimeout = setTimeout(function() {
                    if (_this.disposed) {
                        return;
                    }
                    _this.trigger('clicked', _this, e);
                    for (var i = 0; i < _this.cells.length; i++) {
                        var cell = _this.cells[i];
                        if (cell.listenRowClick && _.isFunction(cell.onRowClicked)) {
                            cell.onRowClicked(_this, e);
                        }
                    }
                    _this.$el.removeClass('mouse-down');
                    delete _this.clickTimeout;
                }, this.DOUBLE_CLICK_WAIT_TIMEOUT);
            }
        },

        /**
         * Returns selected text is available
         *
         * @return {string}
         */
        getSelectedText: function() {
            var text = '';
            if (_.isFunction(window.getSelection)) {
                text = window.getSelection().toString();
            } else if (!_.isUndefined(document.selection) && document.selection.type === 'Text') {
                text = document.selection.createRange().text;
            }
            return text;
        },

        /**
         * @inheritDoc
         */
        makeCell: function(column) {
            var cell = new (column.get('cell'))({
                column: column,
                model: this.model
            });
            if (column.has('align')) {
                cell.$el.removeClass('align-left align-center align-right');
                cell.$el.addClass('align-' + column.get('align'));
            }
            if (!_.isUndefined(cell.skipRowClick) && cell.skipRowClick) {
                cell.$el.addClass('skip-row-click');
            }

            // use columns collection as event bus since there is no alternatives
            this.columns.trigger('afterMakeCell', this, cell);

            return cell;
        }
    });

    return Row;
});
