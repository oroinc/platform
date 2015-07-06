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
            var selectionState = this.datagrid.getSelectionState();
            if (_.isEmpty(selectionState.selectedModels) && selectionState.inset) {
                messenger.notificationFlashMessage('warning', __(this.messages.empty_selection));
            } else {
                MassAction.__super__.execute.call(this);
            }
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         * @private
         */
        getActionParameters: function() {
            var selectionState, collection, idValues, params;
            selectionState = this.datagrid.getSelectionState();
            collection = this.datagrid.collection;
            idValues = _.map(selectionState.selectedModels, function(model) {
                return model.get(this.identifierFieldName);
            }, this);

            params = {
                inset: selectionState.inset ? 1 : 0,
                values: idValues.join(',')
            };

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
