/*jslint nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/modal'
], function ($, _, Backbone, __, mediator, Modal) {
    'use strict';

    var CellSelectionListener;

    /**
     * Listener for entity edit form and datagrid
     *
     * @export  orodatagrid/js/datagrid/listener/cell-selection-listener
     * @class   orodatagrid.datagrid.listener.CellSelectionListener
     * @extends Backbone.Model
     */
    CellSelectionListener = Backbone.Model.extend({

        /** @param {String} */
        changeset: null,

        /** @param {Array} Column names of cells that will be listened for changing their values */
        columns: [],

        /** @param {String} */
        dataField: 'id',

        /**
         * Initialize listener object
         *
         * @param {Object} options
         */
        initialize: function (options) {
            if (!_.has(options, 'changeset')) {
                throw new Error('changeset is not specified');
            }
            this.changeset = options.changeset;

            if (!_.has(options, 'columns')) {
                throw new Error('columns is not specified');
            }
            this.columns = options.columns;

            if (options.dataField) {
                this.dataField = options.dataField;
            }

            this.confirmModal = {};

            CellSelectionListener.__super__.initialize.apply(this, arguments);

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
            _.each(this.confirmModal, function (modal) {
                modal.dispose();
            });
            delete this.confirmModal;

            if (this.disposed) {
                return;
            }
            this.$gridContainer.off(this.gridEvents);
            delete this.$gridContainer;
            delete this.gridEvents;
            CellSelectionListener.__super__.dispose.call(this);
        },

        /**
         * Set datagrid instance
         */
        setDatagridAndSubscribe: function () {
            this.gridEvents = this.getGridEvents();
            this.$gridContainer.on(this.gridEvents);

            this._clearState();
            this._restoreState();

            /** Restore selectors state from pagestate */
            mediator.bind('grid_load:complete', function () {
                this._restoreState();
            }, this);
        },

        /**
         * Collects event handlers for grid container
         *
         * @returns {Object}
         */
        getGridEvents: function () {
            var events = {};
            events['datagrid:change:' + this.gridName] = _.bind(this._onModelEdited, this);
            events['preExecute:refresh:' + this.gridName] = _.bind(this._onExecuteRefreshAction, this);
            events['preExecute:reset:' + this.gridName] = _.bind(this._onExecuteResetAction, this);
            return events;
        },

        /**
         * Process cell editing
         *
         * @param {Backbone.Model} model
         * @protected
         */
        _onModelEdited: function (e, model) {
            var changed = false;

            _.each(this.columns, function (column) {
                if (model.hasChanged(column)) {
                    changed = true;
                }
            });

            if (!changed) {
                return;
            }

            var value = model.get(this.dataField);

            if (!_.isUndefined(value)) {
                this._processValue(value, model);
            }
        },

        /**
         * Fills input referenced by changeset
         *
         * @param {*} id model id
         * @param {Backbone.Model} model
         * @protected
         */
        _processValue: function (id, model) {
            var changeset = this.get('changeset');
            if (!_.has(changeset, id)) {
                changeset[id] = {};
            }

            _.each(this.columns, function (column) {
                changeset[id][column] = model.get(column);
            });

            this.set('changeset', changeset);

            this._synchronizeState();
        },

        /**
         * Clears state of changeset property to empty value
         *
         * @private
         */
        _clearState: function () {
            this.set('changeset', {});
        },

       /**
        * Synchronize value of changeset property with form field and datagrid parameters
        *
        * @private
        */
        _synchronizeState: function () {
            var changeset = this.get('changeset');
            if (this.changeset) {
                $(this.changeset).val(JSON.stringify(changeset));
            }
        },

       /**
        * String into object
        *
        * @param string
        * @return {Array}
        * @private
        */
        _toObject: function (string) {
            if (!string) {
                return {};
            }
            return JSON.parse(string);
        },

         /**
          * Restore values of changeset property
          *
          * @private
          */
        _restoreState: function () {
            var changeset = {};
            if (this.changeset && $(this.changeset).length) {
                changeset = this._toObject($(this.changeset).val());
                this.set('changeset', changeset);
            }
            if (!_.isEmpty(changeset)) {
                mediator.trigger('datagrid:restoreChangeset:' + this.gridName, this.dataField, changeset);
            }
        },

        /**
         * Confirms refresh action that before it will be executed
         *
         * @param {$.Event} e
         * @param {oro.datagrid.action.AbstractAction} action
         * @param {Object} options
         * @private
         */
        _onExecuteRefreshAction: function (e, action, options) {
            this._confirmAction(action, options, 'refresh', {
                title: __('Refresh Confirmation'),
                content: __('Your local changes will be lost. Are you sure you want to refresh grid?')
            });
        },

        /**
         * Confirms reset action that before it will be executed
         *
         * @param {$.Event} e
         * @param {oro.datagrid.action.AbstractAction} action
         * @param {Object} options
         * @private
         */
        _onExecuteResetAction: function (e, action, options) {
            this._confirmAction(action, options, 'reset', {
                title: __('Reset Confirmation'),
                content: __('Your local changes will be lost. Are you sure you want to reset grid?')
            });
        },

        /**
         * Asks user a confirmation if there are local changes, if user confirms then clears state and runs action
         *
         * @param {oro.datagrid.action.AbstractAction} action
         * @param {Object} actionOptions
         * @param {String} type "reset" or "refresh"
         * @param {Object} confirmModalOptions Options for confirm dialog
         * @private
         */
        _confirmAction: function (action, actionOptions, type, confirmModalOptions) {
            this.confirmed = this.confirmed || {};
            if (!this.confirmed[type] && this._hasChanges()) {
                actionOptions.doExecute = false; // do not execute action until it's confirmed
                this._openConfirmDialog(type, confirmModalOptions, function () {
                    // If confirmed, clear state and run action
                    this.confirmed[type] = true;
                    this._clearState();
                    this._synchronizeState();
                    action.run();
                });
            }
            this.confirmed[type] = false;
        },

        /**
         * Returns TRUE if listener contains user changes
         *
         * @return {Boolean}
         * @private
         */
        _hasChanges: function () {
            return !_.isEmpty(this.get('changeset'));
        },

        /**
         * Opens confirm modal dialog
         */
        _openConfirmDialog: function (type, options, callback) {
            if (!this.confirmModal[type]) {
                this.confirmModal[type] = new Modal(_.extend({
                    title: __('Confirmation'),
                    okText: __('OK, got it.'),
                    className: 'modal modal-primary',
                    okButtonClass: 'btn-primary btn-large'
                }, options));
                this.confirmModal[type].on('ok', _.bind(callback, this));
            }
            this.confirmModal[type].open();
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
    CellSelectionListener.init = function (deferred, options) {
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

                listener = new CellSelectionListener(listenerOptions);
                deferred.resolve(listener);
            }).fail(function () {
                deferred.reject();
            });
        } else {
            deferred.reject();
        }
    };

    return CellSelectionListener;
});
