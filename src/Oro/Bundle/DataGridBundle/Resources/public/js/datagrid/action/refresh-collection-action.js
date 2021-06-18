define([
    './abstract-action'
], function(AbstractAction) {
    'use strict';

    /**
     * Refreshes collection
     *
     * @export  oro/datagrid/action/refresh-collection-action
     * @class   oro.datagrid.action.RefreshCollectionAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const RefreshCollectionAction = AbstractAction.extend({
        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * @inheritdoc
         */
        constructor: function RefreshCollectionAction(options) {
            RefreshCollectionAction.__super__.constructor.call(this, options);
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

            RefreshCollectionAction.__super__.initialize.call(this, options);
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
