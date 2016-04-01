define([
    'underscore',
    'backgrid',
    '../select-state-model',
    'backbone'
], function(_, Backgrid, SelectStateModel, Backbone) {
    'use strict';

    var SelectAllHeaderCell;
    var $ = Backbone.$;

    /**
     * Contains mass-selection logic
     *  - watches models selection, keeps reference to selected
     *  - provides mass-selection actions
     *  - listening to models collection events,
     *      fills in 'obj' with proper data for
     *      `backgrid:isSelected` and `backgrid:getSelected`
     *
     * @export  orodatagrid/js/datagrid/header-cell/select-all-header-cell
     * @class   orodatagrid.datagrid.headerCell.SelectAllHeaderCell
     * @extends Backbone.View
     */
    SelectAllHeaderCell = Backbone.View.extend({
        /** @property */
        className: 'select-all-header-cell renderable',

        /** @property */
        tagName: 'th',

        template: '#template-select-all-header-cell',

        selectState: null,

        /**
         * Initializer.
         * Subscribers on events listening
         *
         * @param {Object} options
         * @param {Backgrid.Column} options.column
         * @param {Backbone.Collection} options.collection
         */
        initialize: function(options) {
            var debouncedUpdateState = _.bind(_.debounce(this.updateState, 50), this);
            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }
            this.selectState = new SelectStateModel();
            this.listenTo(this.selectState, 'change', debouncedUpdateState);
            this.listenTo(this.selectState.get('rows'), 'add remove reset', debouncedUpdateState);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.selectState;
            delete this.column;
            SelectAllHeaderCell.__super__.dispose.apply(this, arguments);
        },

        /**
         * Updates state of selection (three states a checkbox: checked, unchecked, or indeterminate)
         */
        updateState: function() {
            var selectedRows = this.selectState.get('rows');
            var inset = this.selectState.get('inset');
            var $checkbox = this.$(':checkbox');
            $checkbox.prop('indeterminate', inset);
            $checkbox.prop('checked', selectedRows.length > 0);
        },

        /**
         * Renders view of the header cell
         *
         * @returns {orodatagrid.datagrid.cell.SelectAllHeaderCell}
         */
        render: function() {
            this.$el.html(_.template($(this.template).text())());
            this.delegateEvents();
            return this;
        },

        delegateEvents: function(events) {
            SelectAllHeaderCell.__super__.delegateEvents.call(this, events);
            // binds event handlers directly to dropdown-menu, because the menu can be attached to document body
            this.$('.dropdown-menu').on('click' + this.eventNamespace(), _.bind(this.onClick, this));
            return this;
        },

        undelegateEvents: function() {
            this.$('.dropdown-menu').off(this.eventNamespace());
            return SelectAllHeaderCell.__super__.undelegateEvents.call(this);
        },

        onClick: function(e) {
            var $el = $(e.target);
            if ($el.is('[data-select]')) {
                // Handles click on checkbox selectAll/selectNone
                if (this.selectState.get('inset') && this.selectState.get('rows').length === 0) {
                    this.collection.trigger('backgrid:selectAll');
                } else {
                    this.collection.trigger('backgrid:selectNone');
                }
                if ($el.is(':checkbox')) {
                    e.stopPropagation();
                }

            } else if ($el.is('[data-select-all]')) {
                // Handles click on selectAll button
                this.collection.trigger('backgrid:selectAll');
                e.preventDefault();

            } else if ($el.is('[data-select-all-visible]')) {
                // Handles click on selectAllVisible button
                this.collection.trigger('backgrid:selectAllVisible');
                e.preventDefault();

            } else if ($el.is('[data-select-none]')) {
                // Handles click on selectNone button
                this.collection.trigger('backgrid:selectNone');
                e.preventDefault();
            }
        }
    });

    return SelectAllHeaderCell;
});
