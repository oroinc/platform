define([
    './abstract-action',
    'oroui/js/tools'
], function(AbstractAction, tools) {
    'use strict';

    /**
     * Resets collection to initial state
     *
     * @export  oro/datagrid/action/reset-collection-action
     * @class   oro.datagrid.action.ResetCollectionAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const ResetCollectionAction = AbstractAction.extend({
        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * @inheritdoc
         */
        constructor: function ResetCollectionAction(options) {
            ResetCollectionAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize action
         *
         * @param {Object} options
         * @param {oro.PageableCollection} options.collection Collection
         * @throws {TypeError} If collection is undefined
         */
        initialize: function(options) {
            const opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }
            this.collection = opts.datagrid.collection;

            ResetCollectionAction.__super__.initialize.call(this, options);
        },

        /**
         * Execute reset collection
         */
        execute: function() {
            this.collection.updateState(tools.deepClone(this.collection.initialState));
            this.collection.fetch({reset: true});
        }
    });

    return ResetCollectionAction;
});
