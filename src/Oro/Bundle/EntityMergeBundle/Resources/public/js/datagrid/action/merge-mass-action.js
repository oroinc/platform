define([
    'underscore',
    'orotranslation/js/translator',
    'oro/datagrid/action/mass-action',
    'oroui/js/messenger'
], function(_, __, MassAction, messenger) {
    'use strict';

    var MergeMassAction;

    /**
     * Merge mass action class.
     *
     * @export  oro/datagrid/action/merge-mass-action
     * @class   oro.datagrid.action.MergeMassAction
     * @extends oro.datagrid.action.MassAction
     */
    MergeMassAction = MassAction.extend({
        /**
         * @inheritDoc
         */
        constructor: function MergeMassAction() {
            MergeMassAction.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         * @constructor
         */
        initialize: function(options) {
            MergeMassAction.__super__.initialize.apply(this, arguments);
            this.on('preExecute', this.onPreExecute, this);
        },

        /**
         * @param {object} event Backbone event object
         * @param {object} options Additional param options needed to stop action
         */
        onPreExecute: function(event, options) {
            var totalRecords;
            var validationMessage;

            var maxLength = this.max_element_count;
            var selectionState = this.datagrid.getSelectionState();
            var isInset = selectionState.inset;
            var length = selectionState.selectedIds.length;

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
