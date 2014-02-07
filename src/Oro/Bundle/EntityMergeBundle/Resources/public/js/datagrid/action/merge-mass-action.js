/* global define */
define(['underscore', 'oro/translator', 'oro/DataGrid/mass-action', 'oro/messenger'],
    /**
     * @param {underscore} _
     * @param {oro/translator} __
     * @param {MassAction} MassAction
     * @param {notificationFlashMessage} messenger
     * @returns {*|Object|void}
     */
    function (_, __, MassAction, messenger) {
        'use strict';

        /**
         * Merge mass action class.
         *
         * @export  oro/entity-merge/merge-mass-action
         * @class   oro.entityMerge.MergeMassAction
         * @classdesc Merge mass action js part
         * @extends oro.DataGrid.MassAction
         */
        return MassAction.extend({

            /**
             * Initialize view
             *
             * @param {Object} options
             * @param {Object} [options.launcherOptions] Options for new instance of launcher object
             * @constructor
             */
            initialize: function (options) {
                /**
                 * @property {Number} max_element_count Max amount of merging elements
                 * @type {Number}
                 */
                var maxLength = this.max_element_count;
                MassAction.prototype.initialize.apply(this, arguments);
                /**
                 * @param {object} event Backbone event object
                 * @param {object} options Additional param options needed to stop action
                 */
                this.on('preExecute', function (event, options) {
                    /**
                     * @typedef {object} SelectedModelsHash Hash map
                     * @typedef {object} SelectionState
                     * @property {SelectedModelsHash} selectedModels
                     * @property {boolean} inset
                     */
                    var selectionState = this.DataGrid.getSelectionState();
                    var isInset = selectionState.inset;
                    var length = Object.keys(selectionState.selectedModels).length;

                    if (!isInset) {
                        var totalRecords = this.DataGrid.collection.state.totalRecords;

                        length = totalRecords - length;
                    }

                    if (length > maxLength) {
                        options['doExecute'] = false;
                        var validationMessage = __('oro.entity_merge.mass_action.validation.maximum_records_error', {number: maxLength});
                        messenger.notificationFlashMessage('error', validationMessage);
                    }

                    if (length < 2) {
                        options['doExecute'] = false;
                        messenger.notificationFlashMessage('error', __('oro.entity_merge.mass_action.validation.minimum_records_error'));
                    }

                }, this);
            }
        });
    });
