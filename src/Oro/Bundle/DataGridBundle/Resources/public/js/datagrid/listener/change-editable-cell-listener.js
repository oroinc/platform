/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    './abstract-grid-change-listener'
], function ($, _, mediator, AbstractGridChangeListener) {
    'use strict';

    var ChangeEditableCellListener;

    /**
     * @export  orodatagrid/js/datagrid/listener/change-editable-cell-listener
     * @class   orodatagrid.datagrid.listener.ChangeEditableCellListener
     * @extends orodatagrid.datagrid.listener.AbstractGridChangeListener
     */
    ChangeEditableCellListener = AbstractGridChangeListener.extend({

        /** @param {Object} */
        $selector: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            if (!_.has(options, 'selector')) {
                throw new Error('Parameter selector is not specified');
            }
            this.$selector = $(options.selector);

            if (!this.$selector.length) {
                throw new Error('DOM element for selector not found');
            }

            ChangeEditableCellListener.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        setDatagridAndSubscribe: function () {
            ChangeEditableCellListener.__super__.setDatagridAndSubscribe.apply(this, arguments);

            /** Restore cells state */
            mediator.bind('grid_load:complete', function () {
                this._restoreState();
            }, this);
        },

        /**
         * @inheritDoc
         */
        _onModelEdited: function (e, model) {
            var changes = model.changed;
            var columns = this.columnName;

            _.each(changes, function (value, column) {
                if (_.indexOf(columns, column) === -1) {
                    delete changes[column];
                }
            });

            if (_.isEmpty(changes)) {
                return;
            }

            var id = model.get(this.dataField);

            if (!_.isUndefined(id)) {
                this._processValue(id, changes);
            }
        },

        /**
         * Process value
         *
         * @param {*}      id      Value of model property with name of this.dataField
         * @param {Object} changes
         */
        _processValue: function (id, changes) {
            var changeset = this.get('changeset');
            if (!_.has(changeset, id)) {
                changeset[id] = {};
            }

            changeset[id] = _.extend(changeset[id], changes);

            this.set('changeset', changeset);

            this._synchronizeState();
        },

        /**
         * @inheritDoc
         */
        _clearState: function () {
            this.set('changeset', {});
        },

        /**
         * @inheritDoc
         */
        _synchronizeState: function () {
            this.$selector.val(JSON.stringify(this.get('changeset')));
        },

       /**
        * String into object
        *
        * @param string
        * @return {Object}
        * @private
        */
        _toObject: function (string) {
            if (!string) {
                return {};
            }
            return JSON.parse(string);
        },

        /**
         * @inheritDoc
         */
        _restoreState: function () {
            var changeset = {};
            if (this.$selector.length) {
                changeset = this._toObject(this.$selector.val());
                this.set('changeset', changeset);
            }
            if (!_.isEmpty(changeset)) {
                mediator.trigger('datagrid:restoreChangeset:' + this.gridName, this.dataField, changeset);
            }
        },

        /**
         * @inheritDoc
         */
        _hasChanges: function () {
            return !_.isEmpty(this.get('changeset'));
        }
    });

    /**
     * Builder interface implementation
     *
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     * @param {jQuery} [options.$el] container for the grid
     * @param {string} [options.gridName] grid name
     * @param {Object} [options.gridPromise] grid builder's promise
     * @param {Object} [options.data] data for grid's collection
     * @param {Object} [options.metadata] configuration for the grid
     */
    ChangeEditableCellListener.init = function (deferred, options) {
        var gridOptions, gridInitialization;
        gridOptions = options.metadata.options || {};
        gridInitialization = options.gridPromise;

        if (gridOptions.cellSelection) {
            gridInitialization.done(function (grid) {
                var listener, listenerOptions;
                listenerOptions = _.defaults({
                    $gridContainer: grid.$el,
                    gridName: grid.name
                }, gridOptions.cellSelection);

                listener = new ChangeEditableCellListener(listenerOptions);
                deferred.resolve(listener);
            }).fail(function () {
                deferred.reject();
            });
        } else {
            deferred.reject();
        }
    };

    return ChangeEditableCellListener;
});
