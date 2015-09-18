define([
    'underscore',
    'backgrid',
    'backbone'
], function(_, Backgrid, Backbone) {
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

        events: {
            'click': 'onClick'
        },

        template: '#template-select-all-header-cell',

        /**
         * Initializer.
         * Subscribers on events listening
         *
         * @param {Object} options
         * @param {Backgrid.Column} options.column
         * @param {Backbone.Collection} options.collection
         */
        initialize: function(options) {
            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.initialState();
            this.listenTo(this.collection, {
                remove: this.removeModel,
                updateState: this.initialState,
                'backgrid:selected': this.selectModel,
                'backgrid:selectAll': this.selectAll,
                'backgrid:selectAllVisible': this.selectAllVisible,
                'backgrid:selectNone': this.selectNone,
                'backgrid:isSelected': this.isSelected,
                'backgrid:getSelected': this.getSelected
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.selectedModels;
            delete this.column;
            SelectAllHeaderCell.__super__.dispose.apply(this, arguments);
        },

        /**
         * Resets selection to initial conditions
         *  - clear selected models set
         *  - reset set type in-set/not-in-set
         * @param {boolean=} inset flag of in-set/not-in-set mode
         */
        initialState: function(inset) {
            this.selectedModels = {};
            /**
             * While using "grid views" functionality,
             *   e.g. switching between views can cause an Object arriving in "inset" property.
             * So, we should add additional check and such case should be perceived the same way as "undefined".
             */
            this.inset = (_.isUndefined(inset) || _.isObject(inset)) ? true : inset;
            this.updateState();
        },

        /**
         * Updates state of selection (three states a checkbox: checked, unchecked, or indeterminate)
         */
        updateState: function() {
            var $checkbox = this.$(':checkbox');
            if (_.isEmpty(this.selectedModels)) {
                $checkbox.prop('indeterminate', false);
                $checkbox.prop('checked', !this.inset);
            } else {
                $checkbox.prop('indeterminate', true);
                $checkbox.prop('checked', false);
            }
        },

        /**
         * Gets selection state
         *
         * @returns {{selectedModels: *, inset: boolean}}
         */
        getSelectionState: function() {
            return {
                selectedModels: this.selectedModels,
                inset: this.inset
            };
        },

        /**
         * Checks if passed model have to be marked as selected
         *
         * @param {Backbone.Model} model
         * @returns {boolean}
         */
        isSelectedModel: function(model) {
            return this.inset === _.has(this.selectedModels, model.id || model.cid);
        },

        /**
         * Removes model from selected models set
         *
         * @param {Backbone.Model} model
         */
        removeModel: function(model) {
            delete this.selectedModels[model.id || model.cid];
            this.updateState();
        },

        /**
         * Adds/removes model to/from selected models set
         *
         * @param {Backbone.Model} model
         * @param {boolean} selected
         */
        selectModel: function(model, selected) {
            if (selected === this.inset) {
                this.selectedModels[model.id || model.cid] = model;
                this.updateState();
            } else {
                this.removeModel(model);
            }
        },

        /**
         * Performs selection of all possible models:
         *  - reset to initial state
         *  - change type of set type as not-inset
         *  - marks all models in collection as selected
         *  start to collect models which have to be excluded
         */
        selectAll: function() {
            this.initialState(false);
            this._markSelected(true);
        },

        /**
         * Reset selection of all possible models:
         *  - reset to initial state
         *  - change type of set type as inset
         *  - marks all models in collection as not selected
         *  start to collect models which have to be included
         */
        selectNone: function() {
            this.initialState();
            this._markSelected(false);
        },

        /**
         * Performs selection of all visible models:
         *  - if necessary reset to initial state
         *  - marks all models in collection as selected
         */
        selectAllVisible: function() {
            if (!this.inset) {
                this.initialState();
            }
            this._markSelected(true);
        },

        /**
         * Marks all models in collection as selected/not selected
         *
         * @param {boolean} selected
         * @private
         */
        _markSelected: function(selected) {
            this.collection.each(function(model) {
                model.trigger('backgrid:select', model, selected);
            });
        },

        /**
         * Renders view of the header cell
         *
         * @returns {orodatagrid.datagrid.cell.SelectAllHeaderCell}
         */
        render: function() {
            this.$el.html(_.template($(this.template).text())());
            return this;
        },

        onClick: function(e) {
            var $el = $(e.target);

            if ($el.is('[data-select]')) {
                // Handles click on checkbox selectAll/selectNone
                if (this.inset && _.isEmpty(this.selectedModels) === this.inset) {
                    this.selectAll();
                } else {
                    this.selectNone();
                }
                if ($el.is(':checkbox')) {
                    e.stopPropagation();
                }

            } else if ($el.is('[data-select-all]')) {
                // Handles click on selectAll button
                this.selectAll();
                e.preventDefault();

            } else if ($el.is('[data-select-all-visible]')) {
                // Handles click on selectAllVisible button
                this.selectAllVisible();
                e.preventDefault();

            } else if ($el.is('[data-select-none]')) {
                // Handles click on selectNone button
                this.selectNone();
                e.preventDefault();
            }
        },

        /**
         * Checks if model is selected
         *  - updates passed obj {selected: true} or {selected: false}
         *
         * @param {Backbone.Model} model
         * @param {Object} obj
         */
        isSelected: function(model, obj) {
            if ($.isPlainObject(obj)) {
                obj.selected = this.isSelectedModel(model);
            }
        },

        /**
         * Collects selected models
         *  - updates passed obj
         *  {
         *      inset: true,// or false
         *      selected: [
         *          // array of models' ids
         *      ]
         *  }
         *
         * @param {Object} obj
         */
        getSelected: function(obj) {
            if ($.isEmptyObject(obj)) {
                obj.selected = _.keys(this.selectedModels);
                obj.inset = this.inset;
            }
        }
    });

    return SelectAllHeaderCell;
});
