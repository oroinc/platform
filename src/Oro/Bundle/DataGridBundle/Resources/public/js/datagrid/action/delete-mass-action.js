define([
    'oroui/js/delete-confirmation',
    'routing',
    'orotranslation/js/translator',
    'underscore',
    './mass-action'
], function(DeleteConfirmation, routing, __, _, MassAction) {
    'use strict';

    var DeleteMassAction;

    /**
     * Delete mass action class.
     *
     * @export  oro/datagrid/action/delete-mass-action
     * @class   oro.datagrid.action.DeleteMassAction
     * @extends oro.datagrid.action.MassAction
     */
    DeleteMassAction = MassAction.extend({
        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to do remove these items?',
            confirm_ok: 'Yes, Delete',
            confirm_cancel: 'Cancel',
            success: 'Selected items were removed.',
            error: 'Selected items were not removed.',
            empty_selection: 'Please, select items to remove.'
        },

        /** @property {Object} */
        confirmMessages: {
            selected_message: 'oro.datagrid.mass_action.delete.selected_message',
            max_limit_message: 'oro.datagrid.mass_action.delete.max_limit_message',
            restricted_access_message: 'oro.datagrid.mass_action.delete.restricted_access_message'
        },

        /** @property {String} */
        confirmMessage: null,

        initialize: function(options) {
            DeleteMassAction.__super__.initialize.apply(this, arguments);
            this.confirmMessage = __(this.defaultMessages.confirm_content);
            this.confirmation = false;
        },

        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            if (this.requestType === 'POST') {
                this.requestType = 'DELETE';
                this.setConfirmMessage(data);
                if (this.reloadData) {
                    this.datagrid.hideLoading();
                }
                this.confirmation = true;
                return DeleteMassAction.__super__.execute.call(this);
            } else {
                MassAction.__super__._onAjaxSuccess.apply(this, arguments);
            }
        },

        execute: function() {
            this.requestType = 'POST';
            this.confirmModal = null;
            if (this.checkSelectionState()) {
                DeleteMassAction.__super__.executeConfiguredAction.call(this);
            }
        },

        getConfirmContentMessage: function() {
            return this.confirmMessage;
        },

        setConfirmMessage: function(data) {
            if (this.isDefined(data.selected) && this.isDefined(data.deletable) && this.isDefined(data.max_limit)) {
                if (data.deletable <= data.max_limit) {
                    if (data.deletable >= data.selected) {
                        this.confirmMessage = __(this.confirmMessages.selected_message, {selected: data.selected});
                    } else {
                        this.confirmMessage = __(this.confirmMessages.restricted_access_message, {deletable: data.deletable});
                    }
                } else {
                    this.confirmMessage = __(this.confirmMessages.max_limit_message, {max_limit: data.max_limit});
                }

            }
        },

        isDefined: function(value) {
            return !_.isUndefined(value);
        }
    });

    return DeleteMassAction;
});
