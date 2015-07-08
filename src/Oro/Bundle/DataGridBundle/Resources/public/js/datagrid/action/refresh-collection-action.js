define([
    './abstract-action'
], function(AbstractAction) {
    'use strict';

    var RefreshCollectionAction;

    /**
     * Refreshes collection
     *
     * @export  oro/datagrid/action/refresh-collection-action
     * @class   oro.datagrid.action.RefreshCollectionAction
     * @extends oro.datagrid.action.AbstractAction
     */
    RefreshCollectionAction = AbstractAction.extend({
        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * Initialize action
         *
         * @param {Object} options
         * @param {oro.PageableCollection} options.collection Collection
         * @throws {TypeError} If collection is undefined
         */
        initialize: function(options) {
            var opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }
            this.collection = opts.datagrid.collection;

            RefreshCollectionAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * Execute refresh collection
         */
        execute: function() {
            this.datagrid.setAdditionalParameter('refresh', true);
            this.collection.fetch({reset: true});
            this.datagrid.removeAdditionalParameter('refresh');
        }
    });

    return RefreshCollectionAction;
});
