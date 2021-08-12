define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/modal',
    './abstract-action'
], function(_, messenger, __, Modal, AbstractAction) {
    'use strict';

    /**
     * Basic mass action class.
     *
     * @export  oro/datagrid/action/mass-action
     * @class   oro.datagrid.action.MassAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const MassAction = AbstractAction.extend({
        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Mass Action Confirmation',
            confirm_content: 'Are you sure you want to do this?',
            confirm_ok: 'Yes, do it',
            confirm_cancel: 'Cancel',
            success: 'Mass action performed.',
            error: 'Mass action is not performed.',
            empty_selection: 'Please, select items to perform mass action.'
        },

        /**
         * @inheritdoc
         */
        constructor: function MassAction(options) {
            MassAction.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            MassAction.__super__.initialize.call(this, options);

            const extendedOptions = {};
            extendedOptions[this.datagrid.name] = this.datagrid.collection.urlParams || {};

            _.extend(this.route_parameters, extendedOptions, {
                gridName: this.datagrid.name,
                actionName: this.name
            });
        },

        /**
         * Ask a confirmation and execute mass action.
         */
        execute: function() {
            if (this.checkSelectionState()) {
                MassAction.__super__.execute.call(this);
            }
        },

        /**
         * Checks if any records are selected.
         *
         * @returns {boolean}
         */
        checkSelectionState: function() {
            const selectionState = this.datagrid.getSelectionState();
            if (selectionState.selectedIds.length === 0 && selectionState.inset) {
                messenger.notificationFlashMessage('warning', __(this.messages.empty_selection));
                return false;
            }

            return true;
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         * @private
         */
        getActionParameters: function() {
            const selectionState = this.datagrid.getSelectionState();
            const collection = this.datagrid.collection;
            const stateKey = collection.stateHashKey();
            let params = {
                inset: selectionState.inset ? 1 : 0,
                values: selectionState.selectedIds.join(',')
            };

            params[stateKey] = collection.stateHashValue();
            params = collection.processFiltersParams(params, null, 'filters');

            return params;
        },

        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            this.datagrid.resetSelectionState();
            MassAction.__super__._onAjaxSuccess.call(this, data, textStatus, jqXHR);
        }
    });

    return MassAction;
});
