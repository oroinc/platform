/*global define*/
define(['./abstract-action'
    ], function (AbstractAction) {
    'use strict';

    /**
     * Refreshes collection
     *
     * @export  orodatagrid/js/datagrid/action/refresh-collection-action
     * @class   orodatagrid.datagrid.action.RefreshCollectionAction
     * @extends orodatagrid.datagrid.action.AbstractAction
     */
    return AbstractAction.extend({

        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * Initialize action
         *
         * @param {Object} options
         * @param {oro.PageableCollection} options.collection Collection
         * @throws {TypeError} If collection is undefined
         */
        initialize: function (options) {
            var opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError("'datagrid' is required");
            }
            this.collection = opts.datagrid.collection;

            AbstractAction.prototype.initialize.apply(this, arguments);
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
});
