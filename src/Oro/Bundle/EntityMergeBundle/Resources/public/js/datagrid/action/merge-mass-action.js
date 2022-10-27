define([
    'underscore',
    'orotranslation/js/translator',
    'oro/datagrid/action/mass-action',
    'oroui/js/messenger'
], function(_, __, MassAction, messenger) {
    'use strict';

    /**
     * Merge mass action class.
     *
     * @export  oro/datagrid/action/merge-mass-action
     * @class   oro.datagrid.action.MergeMassAction
     * @extends oro.datagrid.action.MassAction
     */
    const MergeMassAction = MassAction.extend({
        /**
         * @inheritdoc
         */
        constructor: function MergeMassAction(options) {
            MergeMassAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         * @constructor
         */
        initialize: function(options) {
            MergeMassAction.__super__.initialize.call(this, options);
            this.on('preExecute', this.onPreExecute, this);
        },

        /**
         * @param {object} event Backbone event object
         * @param {object} options Additional param options needed to stop action
         */
        onPreExecute: function(event, options) {
            let totalRecords;
            let validationMessage;

            const maxLength = this.max_element_count;
            const selectionState = this.datagrid.getSelectionState();
            const isInset = selectionState.inset;
            let length = selectionState.selectedIds.length;

            if (!isInset) {
                totalRecords = this.datagrid.collection.state.totalRecords;
                length = totalRecords - length;
            }

            if (length > maxLength) {
                options.doExecute = false;
                validationMessage = __('oro.entity_merge.mass_action.validation.maximum_records_error',
                    {number: maxLength});
                messenger.notificationFlashMessage('error', validationMessage);
            }

            if (length < 2) {
                options.doExecute = false;
                validationMessage = __('oro.entity_merge.mass_action.validation.minimum_records_error',
                    {number: maxLength});
                messenger.notificationFlashMessage('error', validationMessage);
            }
        }
    });

    return MergeMassAction;
});
