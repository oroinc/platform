define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Backgrid = require('backgrid');
    const SelectStateModel = require('../select-state-model');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!orodatagrid/templates/datagrid/select-all-header-cell.html');

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
     * @extends BaseView
     */
    const SelectAllHeaderCell = BaseView.extend({
        optionNames: ['column'],

        _attributes: Backgrid.Cell.prototype._attributes,

        keepElement: false,
        /** @property */
        className: 'select-all-header-cell renderable',

        /** @property */
        tagName: 'th',

        template: template,

        selectState: null,

        /**
         * @inheritdoc
         */
        constructor: function SelectAllHeaderCell(options) {
            SelectAllHeaderCell.__super__.constructor.call(this, options);
        },

        /**
         * Initializer.
         * Subscribers on events listening
         *
         * @param {Object} options
         * @param {Backgrid.Column} options.column
         * @param {Backbone.Collection} options.collection
         */
        initialize: function(options) {
            const debouncedUpdateState = _.debounce(this.updateState.bind(this), 50);
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }
            this.selectState = new SelectStateModel();
            this.listenTo(this.selectState, 'change', debouncedUpdateState);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.selectState;
            delete this.column;
            SelectAllHeaderCell.__super__.dispose.call(this);
        },

        /**
         * Updates state of selection (three states a checkbox: checked, unchecked, or indeterminate)
         */
        updateState: function(selectState) {
            this.$('[data-select]:checkbox').prop({
                indeterminate: !selectState.isEmpty(),
                checked: !selectState.get('inset')
            });
        },

        /**
         * Renders view of the header cell
         *
         * @returns {orodatagrid.datagrid.cell.SelectAllHeaderCell}
         */
        render: function() {
            this.$el.html(this.getTemplateFunction()(this.getTemplateData()));
            this.delegateEvents();
            return this;
        },

        delegateEvents: function(events) {
            SelectAllHeaderCell.__super__.delegateEvents.call(this, events);
            // binds event handlers directly to dropdown-menu, because the menu can be attached to document body
            this.$('.dropdown-menu').on('click' + this.eventNamespace(), this.onDropdownClick.bind(this));
            // binds event handlers directly to checkbox, because a toggle-dropdown stops event propagation
            this.$('[data-select]:checkbox').on('click' + this.eventNamespace(), this.onCheckboxClick.bind(this));
            return this;
        },

        undelegateEvents: function() {
            if (this.$el) {
                this.$('.dropdown-menu').off(this.eventNamespace());
                this.$('[data-select]:checkbox').off(this.eventNamespace());
            }
            return SelectAllHeaderCell.__super__.undelegateEvents.call(this);
        },

        onCheckboxClick: function(e) {
            if (this.selectState.get('inset') && this.selectState.isEmpty()) {
                this.collection.trigger('backgrid:selectAll');
            } else {
                this.collection.trigger('backgrid:selectNone');
            }
            e.stopPropagation();
        },

        onDropdownClick: function(e) {
            const $el = $(e.target);
            if ($el.is('[data-select-all]')) {
                // Handles click on selectAll button
                this.collection.trigger('backgrid:selectAll');
            } else if ($el.is('[data-select-all-visible]')) {
                // Handles click on selectAllVisible button
                this.collection.trigger('backgrid:selectAllVisible');
            } else if ($el.is('[data-select-none]')) {
                // Handles click on selectNone button
                this.collection.trigger('backgrid:selectNone');
            }
            e.preventDefault();
        }
    });

    return SelectAllHeaderCell;
});
