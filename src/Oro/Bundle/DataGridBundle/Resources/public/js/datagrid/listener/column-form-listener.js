define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    './abstract-grid-change-listener'
], function($, _, mediator, AbstractGridChangeListener) {
    'use strict';

    var ColumnFormListener;

    /**
     * Listener for entity edit form and datagrid
     *
     * @export  orodatagrid/js/datagrid/listener/column-form-listener
     * @class   orodatagrid.datagrid.listener.ColumnFormListener
     * @extends orodatagrid.datagrid.listener.AbstractGridChangeListener
     */
    ColumnFormListener = AbstractGridChangeListener.extend({
        /** @param {Object} */
        selectors: {
            included: null,
            excluded: null
        },

        /**
         * @inheritDoc
         */
        constructor: function ColumnFormListener() {
            ColumnFormListener.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (!_.has(options, 'selectors')) {
                throw new Error('Field selectors is not specified');
            }
            this.selectors = options.selectors;

            this.grid = options.grid;
            this.confirmModal = {};

            ColumnFormListener.__super__.initialize.apply(this, arguments);

            this.selectRows();
            this.listenTo(options.grid.collection, {
                sync: this.selectRows,
                excludeRow: this._excludeRow,
                includeRow: this._includeRow,
                setState: this.setState
            });
        },

        /**
         * @inheritDoc
         */
        setDatagridAndSubscribe: function() {
            ColumnFormListener.__super__.setDatagridAndSubscribe.apply(this, arguments);

            /** Restore include/exclude state from pagestate */
            mediator.bind('pagestate_restored', function() {
                this._restoreState();
            }, this);
        },

        /**
         * Selecting rows
         */
        selectRows: function() {
            var columnName = this.columnName;

            this.grid.collection.each(function(model) {
                var isActive = model.get(columnName);
                model.trigger('backgrid:selected', model, isActive);
            });
        },

        /**
         * @inheritDoc
         */
        _processValue: function(id, model) {
            id = String(id);

            var isActive = model.get(this.columnName);

            if (isActive) {
                this._includeRow(id);
            } else {
                this._excludeRow(id);
            }

            model.trigger('backgrid:selected', model, isActive);
        },

        _excludeRow: function(id) {
            var included = this.get('included');
            var excluded = this.get('excluded');

            if (_.contains(included, id)) {
                included = _.without(included, id);
            } else {
                excluded = _.union(excluded, [id]);
            }

            this.set('included', included);
            this.set('excluded', excluded);

            this._synchronizeState();
        },

        _includeRow: function(id) {
            var included = this.get('included');
            var excluded = this.get('excluded');

            if (_.contains(excluded, id)) {
                excluded = _.without(excluded, id);
            } else {
                included = _.union(included, [id]);
            }

            this.set('included', included);
            this.set('excluded', excluded);

            this._synchronizeState();
        },

        /**
         * Sets included and excluded elements ids using provided arrays
         */
        setState: function(included, excluded) {
            this.set('included', included);
            this.set('excluded', excluded);
            this._synchronizeState();
        },

        /**
         * @inheritDoc
         */
        _clearState: function() {
            this.set('included', []);
            this.set('excluded', []);
        },

        /**
         * @inheritDoc
         */
        _synchronizeState: function() {
            var included = this.get('included');
            var excluded = this.get('excluded');
            if (this.selectors.included) {
                $(this.selectors.included).val(included.join(','));
            }
            if (this.selectors.excluded) {
                $(this.selectors.excluded).val(excluded.join(','));
            }
            mediator.trigger('datagrid:setParam:' + this.gridName, 'data_in', included);
            mediator.trigger('datagrid:setParam:' + this.gridName, 'data_not_in', excluded);
        },

        /**
         * Explode string into int array
         *
         * @param string
         * @return {Array}
         * @private
         */
        _explode: function(string) {
            if (!string) {
                return [];
            }
            return _.map(string.split(','), function(val) {
                return val ? String(val) : null;
            });
        },

        /**
          * Restore values of include and exclude properties
          *
          * @private
          */
        _restoreState: function() {
            var included = [];
            var excluded = [];
            var columnName = this.columnName;
            if (this.selectors.included && $(this.selectors.included).length) {
                included = this._explode($(this.selectors.included).val());
                this.set('included', included);
            }
            if (this.selectors.excluded && $(this.selectors.excluded).length) {
                excluded = this._explode($(this.selectors.excluded).val());
                this.set('excluded', excluded);
            }

            _.each(this.grid.collection.models, function(model) {
                var isActive = model.get(columnName);
                var modelId = String(model.id);
                if (!isActive && _.contains(included, modelId)) {
                    model.set(columnName, true);
                }
                if (isActive && _.contains(excluded, modelId)) {
                    model.set(columnName, false);
                }
            });

            if (included || excluded) {
                mediator.trigger('datagrid:setParam:' + this.gridName, 'data_in', included);
                mediator.trigger('datagrid:setParam:' + this.gridName, 'data_not_in', excluded);
                mediator.trigger('datagrid:restoreState:' + this.gridName,
                    this.columnName, this.dataField, included, excluded);
            }
        },

        /**
         * @inheritDoc
         */
        _hasChanges: function() {
            return !_.isEmpty(this.get('included')) || !_.isEmpty(this.get('excluded'));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.grid;
            ColumnFormListener.__super__.dispose.apply(this, arguments);
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
    ColumnFormListener.init = function(deferred, options) {
        var gridOptions = options.metadata.options || {};
        var gridInitialization = options.gridPromise;

        var gridListenerOptions = gridOptions.rowSelection || gridOptions.columnListener; // for BC

        if (gridListenerOptions) {
            gridInitialization.done(function(grid) {
                var listenerOptions = _.defaults({
                    $gridContainer: grid.$el,
                    gridName: grid.name,
                    grid: grid
                }, gridListenerOptions);

                var listener = new ColumnFormListener(listenerOptions);
                deferred.resolve(listener);
            }).fail(function() {
                deferred.reject();
            });
        } else {
            deferred.reject();
        }
    };

    return ColumnFormListener;
});
