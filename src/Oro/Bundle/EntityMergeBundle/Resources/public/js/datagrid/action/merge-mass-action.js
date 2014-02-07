/* global define */
define(['underscore', 'oro/translator', 'oro/datagrid/mass-action', 'oro/messenger'],
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
         * @extends oro.datagrid.MassAction
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
                var max_length = this.max_element_count;
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
                    var selectionState = this.datagrid.getSelectionState();
                    /**
                     * @type {boolean}
                     */
                    var isInset = selectionState.inset;
                    /**
                     * @type {Number}
                     */
                    var length = Object.keys(selectionState.selectedModels).length;

                    if (!isInset) {
                        /**
                         * @type {Number}
                         */
                        var totalRecords = this.datagrid.collection.state.totalRecords;

                        length = totalRecords - length;
                    }

                    if (length > max_length) {
                        /**
                         * @type {boolean} Need or not to execute action
                         */
                        options['doExecute'] = false;
                        /**
                         * @type {string}
                         */
                        var validationMessage = __('Mass action validation maximum error').replace('{{number}}', max_length);
                        messenger.notificationFlashMessage('error', validationMessage);
                    }

                    if (length < 2) {
                        /**
                         * @type {boolean} Need or not to execute action
                         */
                        options['doExecute'] = false;
                        messenger.notificationFlashMessage('error', __('Mass action validation minimum error'));
                    }

                }, this);
            }
        });
    });
