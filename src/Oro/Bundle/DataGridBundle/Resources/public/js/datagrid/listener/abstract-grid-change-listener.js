define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal',
    './abstract-listener'
], function($, _, __, Modal, AbstractListener) {
    'use strict';

    var AbstractGridChangeListener;

    /**
     * @export  orodatagrid/js/datagrid/listener/abstract-grid-change-listener
     * @class   orodatagrid.datagrid.listener.AbstractGridChangeListener
     * @extends orodatagrid.datagrid.listener.AbstractListener
     */
    AbstractGridChangeListener = AbstractListener.extend({

        /** @param {Object} */
        confirmModal: {},

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            _.each(this.confirmModal, function(modal) {
                modal.dispose();
            });
            delete this.confirmModal;
            AbstractGridChangeListener.__super__.dispose.call(this);
        },

        /**
         * Set datagrid instance
         */
        setDatagridAndSubscribe: function() {
            AbstractGridChangeListener.__super__.setDatagridAndSubscribe.apply(this, arguments);

            this._clearState();
            this._restoreState();
        },

        /**
         * @inheritDoc
         */
        getGridEvents: function() {
            var events = AbstractGridChangeListener.__super__.getGridEvents.call(this);
            events['preExecute:refresh:' + this.gridName] = _.bind(this._onExecuteRefreshAction, this);
            events['preExecute:reset:' + this.gridName] = _.bind(this._onExecuteResetAction, this);
            return events;
        },

        /**
         * Clears state of include and exclude properties to empty values
         *
         * @protected
         * @abstract
         */
        _clearState: function() {
            throw new Error('_clearState method is abstract and must be implemented');
        },

        /**
         * Synchronize values of include and exclude properties with form fields and datagrid parameters
         *
         * @protected
         * @abstract
         */
        _synchronizeState: function() {
            throw new Error('_synchronizeState method is abstract and must be implemented');
        },

        /**
         * Restore values of include and exclude properties
         *
         * @protected
         * @abstract
         */
        _restoreState: function() {
            throw new Error('_restoreState method is abstract and must be implemented');
        },

        /**
         * Confirms refresh action that before it will be executed
         *
         * @param {$.Event} e
         * @param {oro.datagrid.action.AbstractAction} action
         * @param {Object} options
         * @protected
         */
        _onExecuteRefreshAction: function(e, action, options) {
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
         * @protected
         */
        _onExecuteResetAction: function(e, action, options) {
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
         * @protected
         */
        _confirmAction: function(action, actionOptions, type, confirmModalOptions) {
            this.confirmed = this.confirmed || {};
            if (!this.confirmed[type] && this._hasChanges()) {
                actionOptions.doExecute = false; // do not execute action until it's confirmed
                this._openConfirmDialog(type, confirmModalOptions, function() {
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
         * @protected
         * @abstract
         */
        _hasChanges: function() {
            throw new Error('_hasChanges method is abstract and must be implemented');
        },

        /**
         * Opens confirm modal dialog
         *
         * @param {String} type "reset" or "refresh"
         * @param {Object} options
         * @param {Object} callback
         * @protected
         */
        _openConfirmDialog: function(type, options, callback) {
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

    return AbstractGridChangeListener;
});
