define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/modal',
    './abstract-action'
], function(_, messenger, __, Modal, AbstractAction) {
    'use strict';

    var MassAction;

    /**
     * Basic mass action class.
     *
     * @export  oro/datagrid/action/mass-action
     * @class   oro.datagrid.action.MassAction
     * @extends oro.datagrid.action.AbstractAction
     */
    MassAction = AbstractAction.extend({
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

        initialize: function(options) {
            MassAction.__super__.initialize.apply(this, arguments);

            var extendedOptions = {};
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
            var selectionState = this.datagrid.getSelectionState();
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
            var selectionState = this.datagrid.getSelectionState();
            var collection = this.datagrid.collection;
            var stateKey = collection.stateHashKey();
            var params = {
                inset: selectionState.inset ? 1 : 0,
                values: selectionState.selectedIds.join(',')
            };

            params[stateKey] = collection.stateHashValue();
            params = collection.processFiltersParams(params, null, 'filters');

            return params;
        },

        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            this.datagrid.resetSelectionState();
            MassAction.__super__._onAjaxSuccess.apply(this, arguments);
        }
    });

    return MassAction;
});
