define([
    './abstract-action',
    'orodatagrid/js/datagrid/dropdown-select-choice-launcher'
], function(AbstractAction, DropdownSelectChoiceLauncher) {
    'use strict';

    var SelectDataAppearanceAction;

    /**
     * Resets collection to initial state
     *
     * @export  oro/datagrid/action/reset-collection-action
     * @class   oro.datagrid.action.ResetCollectionAction
     * @extends oro.datagrid.action.AbstractAction
     */
    SelectDataAppearanceAction =  AbstractAction.extend({
        /** @property {Function} */
        launcher: DropdownSelectChoiceLauncher,

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
            this.datagrid = opts.datagrid;

            SelectDataAppearanceAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * Execute reset collection
         */
        execute: function(data) {
            this.datagrid.changeAppearance(data.key, data.item.options);
        }
    });

    return SelectDataAppearanceAction;
});
