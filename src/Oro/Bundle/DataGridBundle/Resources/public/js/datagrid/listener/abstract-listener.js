/*jslint browser: true, nomen: true*/
/*global define*/
define([
    'underscore',
    'jquery',
    'backbone'
], function (_, $, Backbone) {
    'use strict';

    var AbstractListener;

    /**
     * Abstarct listener for datagrid
     *
     * @export  orodatagrid/js/datagrid/listener/abstract-listener
     * @class   orodatagrid.datagrid.listener.AbstractListener
     * @extends Backbone.Model
     */
    AbstractListener = Backbone.Model.extend({
        /** @param {String|Array} Column name of cells that will be listened for changing their values */
        columnName: 'id',

        /** @param {String} Model field that contains data */
        dataField: 'id',

        /**
         * Initialize listener object
         *
         * @param {Object} options
         */
        initialize: function (options) {
            if (!_.has(options, 'columnName')) {
                throw new Error('Data column name is not specified');
            }
            this.columnName = options.columnName;

            if (options.dataField) {
                this.dataField = options.dataField;
            }

            AbstractListener.__super__.initialize.apply(this, arguments);

            if (!options.$gridContainer) {
                throw new Error('gridSelector is not specified');
            }
            this.$gridContainer = options.$gridContainer;
            this.gridName = options.gridName;

            this.setDatagridAndSubscribe();
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.$gridContainer.off(this.gridEvents);
            delete this.$gridContainer;
            delete this.gridEvents;
            AbstractListener.__super__.dispose.call(this);
        },

        /**
         * Set datagrid instance
         */
        setDatagridAndSubscribe: function () {
            this.gridEvents = this.getGridEvents();
            this.$gridContainer.on(this.gridEvents);
        },

        /**
         * Collects event handlers for grid container
         *
         * @returns {Object}
         */
        getGridEvents: function () {
            var events = {};
            events['datagrid:change:' + this.gridName] = _.bind(this._onModelEdited, this);
            return events;
        },

        /**
         * Process cell editing
         *
         * @param {$.Event} e
         * @param {Backbone.Model} model
         * @protected
         */
        _onModelEdited: function (e, model) {
            if (!model.hasChanged(this.columnName)) {
                return;
            }

            var value = model.get(this.dataField);

            if (!_.isUndefined(value)) {
                this._processValue(value, model);
            }
        },

        /**
         * Process value
         *
         * @param {*} value Value of model property with name of this.dataField
         * @param {Backbone.Model} model
         * @protected
         * @abstract
         */
        _processValue: function (value, model) {
            throw new Error('_processValue method is abstract and must be implemented');
        }
    });

    return AbstractListener;
});
